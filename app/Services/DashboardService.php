<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Enums\VoteType;
use App\Models\AwardEvent;
use App\Models\Category;
use App\Models\Nominee;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Admin dashboard figures. Every number is a single aggregate query (no
 * models loaded into memory) and the bundle is cached for a minute so the
 * dashboard stays cheap during traffic spikes.
 */
class DashboardService
{
    public function stats(?AwardEvent $event = null): array
    {
        return Cache::remember('admin.dashboard.'.($event?->id ?? 'all'), 60, fn () => [
            'totals' => $this->totals($event),
            'charts' => $this->charts($event),
        ]);
    }

    private function totals(?AwardEvent $event): array
    {
        $votes = Vote::query()
            ->valid()
            ->when($event, fn ($q) => $q->where('award_event_id', $event->id))
            ->selectRaw('COALESCE(SUM(votes), 0) as total')
            ->selectRaw('COALESCE(SUM(CASE WHEN vote_type = ? THEN votes ELSE 0 END), 0) as free', [VoteType::Free->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN vote_type = ? THEN votes ELSE 0 END), 0) as paid', [VoteType::Paid->value])
            ->first();

        $payments = Payment::query()
            ->when($event, fn ($q) => $q->whereHasMorph('payable', [VoteOrder::class], fn ($o) => $o->where('award_event_id', $event->id)))
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as revenue', [PaymentStatus::Success->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as successful', [PaymentStatus::Success->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed', [PaymentStatus::Failed->value])
            ->first();

        $orders = VoteOrder::query()->when($event, fn ($q) => $q->where('award_event_id', $event->id))->count();

        $nominees = Nominee::query()
            ->when($event, fn ($q) => $q->whereIn('category_id', $event->categories()->select('id')))
            ->count();

        $categories = Category::query()->when($event, fn ($q) => $q->where('award_event_id', $event->id))->count();

        return [
            'total_votes' => (int) $votes->total,
            'free_votes' => (int) $votes->free,
            'paid_votes' => (int) $votes->paid,
            'revenue' => (float) $payments->revenue,
            'orders' => $orders,
            'successful_payments' => (int) $payments->successful,
            'failed_payments' => (int) $payments->failed,
            'voters' => User::voters()->count(),
            'nominees' => $nominees,
            'categories' => $categories,
            'active_events' => AwardEvent::whereIn('status', [EventStatus::Voting->value, EventStatus::Paused->value, EventStatus::Upcoming->value])->count(),
        ];
    }

    private function charts(?AwardEvent $event): array
    {
        $since = now()->subDays(29)->startOfDay();
        $days = collect(range(0, 29))->map(fn ($i) => $since->copy()->addDays($i)->toDateString());

        $votesPerDay = Vote::query()->valid()
            ->when($event, fn ($q) => $q->where('award_event_id', $event->id))
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('COALESCE(SUM(CASE WHEN vote_type = ? THEN votes ELSE 0 END), 0) as free', [VoteType::Free->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN vote_type = ? THEN votes ELSE 0 END), 0) as paid', [VoteType::Paid->value])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy('day');

        $revenuePerDay = Payment::query()
            ->where('status', PaymentStatus::Success->value)
            ->where('paid_at', '>=', $since)
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->pluck('total', 'day');

        $byCategory = Vote::query()->valid()
            ->join('categories', 'categories.id', '=', 'votes.category_id')
            ->when($event, fn ($q) => $q->where('votes.award_event_id', $event->id))
            ->selectRaw('categories.name as label, SUM(votes.votes) as total')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $byNominee = Vote::query()->valid()
            ->join('nominees', 'nominees.id', '=', 'votes.nominee_id')
            ->when($event, fn ($q) => $q->where('votes.award_event_id', $event->id))
            ->selectRaw('nominees.name as label, SUM(votes.votes) as total')
            ->groupBy('nominees.id', 'nominees.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $paymentStatus = Payment::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Voting activity: number of ledger entries per hour over the last 24h.
        $hourStart = now()->subHours(23)->startOfHour();
        // SUBSTR(datetime, 1, 13) = "YYYY-MM-DD HH" on both MySQL and SQLite.
        $activity = Vote::query()
            ->where('created_at', '>=', $hourStart)
            ->when($event, fn ($q) => $q->where('award_event_id', $event->id))
            ->selectRaw('SUBSTR(created_at, 1, 13) as hour_bucket, COUNT(*) as total')
            ->groupBy(DB::raw('SUBSTR(created_at, 1, 13)'))
            ->pluck('total', 'hour_bucket');
        $hours = collect(range(0, 23))->map(fn ($i) => $hourStart->copy()->addHours($i));

        return [
            'days' => $days->map(fn ($d) => Carbon::parse($d)->format('j M'))->all(),
            'votes_free' => $days->map(fn ($d) => (int) ($votesPerDay[$d]->free ?? 0))->all(),
            'votes_paid' => $days->map(fn ($d) => (int) ($votesPerDay[$d]->paid ?? 0))->all(),
            'revenue' => $days->map(fn ($d) => round((float) ($revenuePerDay[$d] ?? 0), 2))->all(),
            'by_category' => $this->pairs($byCategory),
            'by_nominee' => $this->pairs($byNominee),
            'payment_status' => ['labels' => $paymentStatus->keys()->map(fn ($s) => ucfirst($s))->all(), 'data' => $paymentStatus->values()->map(fn ($v) => (int) $v)->all()],
            'activity' => [
                'labels' => $hours->map(fn ($h) => $h->format('ga'))->all(),
                'data' => $hours->map(fn ($h) => (int) ($activity[$h->format('Y-m-d H')] ?? 0))->all(),
            ],
        ];
    }

    private function pairs(Collection $rows): array
    {
        return ['labels' => $rows->pluck('label')->all(), 'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all()];
    }
}
