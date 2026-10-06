<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\VoteType;
use App\Models\AwardEvent;
use App\Models\Donation;
use App\Models\EventBooking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Aggregate reports. The route has no `can:` middleware because each section
 * has its own permission, checked here. Every figure is computed with
 * grouped SQL aggregates; rows are never loaded wholesale.
 */
class ReportController extends AdminController
{
    private const PAYABLE_LABELS = [
        'vote_order' => 'Vote orders',
        'donation' => 'Donations',
        'event_booking' => 'Event bookings',
    ];

    public function index(Request $request): View
    {
        $can = $this->permissions($request);
        abort_unless(in_array(true, $can, true), 403);

        return view('admin.reports.index', [
            'can' => $can,
            'events' => AwardEvent::orderBy('name')->pluck('name', 'id'),
            'currency' => config('awards.currency'),
            'voting' => $can['voting'] ? $this->votingReport($request) : null,
            'financial' => $can['financial'] ? $this->financialReport($request) : null,
            'voters' => $can['voters'] ? $this->votersReport($request) : null,
        ]);
    }

    public function export(Request $request, string $report): StreamedResponse
    {
        $can = $this->permissions($request);

        return match ($report) {
            'votes-by-nominee' => $this->exportVotesByNominee($request, $can['voting']),
            'votes-by-category' => $this->exportVotesByCategory($request, $can['voting']),
            'revenue-daily' => $this->exportRevenueDaily($request, $can['financial']),
            'voters' => $this->exportVoters($request, $can['voters']),
            default => abort(404),
        };
    }

    /* ------------------------------------------------------------------ */
    /* Sections */
    /* ------------------------------------------------------------------ */

    private function votingReport(Request $request): array
    {
        $byType = $this->validVotes($request)
            ->selectRaw('votes.vote_type as vote_type, SUM(votes.votes) as total')
            ->groupBy('votes.vote_type')
            ->toBase()
            ->pluck('total', 'vote_type')
            ->map(fn ($v) => (int) $v);

        $byEvent = $this->validVotes($request)
            ->join('award_events', 'award_events.id', '=', 'votes.award_event_id')
            ->selectRaw('award_events.name as name, SUM(votes.votes) as total')
            ->groupBy('award_events.id', 'award_events.name')
            ->orderByDesc('total')
            ->toBase()
            ->get();

        $daily = $this->validVotes($request)
            ->selectRaw('DATE(votes.created_at) as day, SUM(votes.votes) as total')
            ->groupBy(DB::raw('DATE(votes.created_at)'))
            ->orderBy('day')
            ->toBase()
            ->get();

        $byCategory = $this->votesByCategoryQuery($request)->limit(20)->get();
        $byNominee = $this->votesByNomineeQuery($request)->limit(25)->get();

        return [
            'total' => (int) $byType->sum(),
            'free' => $byType[VoteType::Free->value] ?? 0,
            'paid' => $byType[VoteType::Paid->value] ?? 0,
            'adjustments' => $byType[VoteType::AdminAdjustment->value] ?? 0,
            'byEvent' => $byEvent,
            'byCategory' => $byCategory,
            'byNominee' => $byNominee,
            'dailyChart' => $this->chart('line', $daily->pluck('day')->all(), [
                ['label' => 'Valid votes', 'data' => $daily->pluck('total')->map(fn ($v) => (int) $v)->all()],
            ]),
            'categoryChart' => $this->chart('bar', $byCategory->take(10)->pluck('category')->all(), [
                ['label' => 'Votes', 'data' => $byCategory->take(10)->pluck('total')->map(fn ($v) => (int) $v)->all()],
            ], horizontal: true),
        ];
    }

    private function financialReport(Request $request): array
    {
        $byStatus = $this->payments($request)
            ->selectRaw('status, COUNT(*) as attempts, COALESCE(SUM(amount), 0) as total')
            ->groupBy('status')
            ->toBase()
            ->get()
            ->keyBy('status');

        $byType = $this->payments($request)
            ->where('status', PaymentStatus::Success->value)
            ->selectRaw('payable_type, COUNT(*) as payments, COALESCE(SUM(amount), 0) as total')
            ->groupBy('payable_type')
            ->orderByDesc('total')
            ->toBase()
            ->get()
            ->map(fn ($row) => (object) [
                'label' => self::PAYABLE_LABELS[$row->payable_type] ?? $row->payable_type,
                'payments' => (int) $row->payments,
                'total' => (string) $row->total,
            ]);

        $daily = $this->dailyRevenueQuery($request)->get();

        $paidSum = fn (Builder $query): string => (string) $query
            ->where('status', OrderStatus::Paid->value)
            ->when($request->integer('event'), fn ($q, $event) => $q->where('award_event_id', $event))
            ->tap(fn ($q) => $this->applyDateRange($q, $request))
            ->sum('amount');

        $status = fn (PaymentStatus $s, string $field) => $byStatus->get($s->value)?->{$field} ?? 0;

        return [
            'revenue' => (string) $status(PaymentStatus::Success, 'total'),
            'refunded' => (string) $status(PaymentStatus::Refunded, 'total'),
            'successCount' => (int) $status(PaymentStatus::Success, 'attempts'),
            'failedCount' => (int) $status(PaymentStatus::Failed, 'attempts') + (int) $status(PaymentStatus::Abandoned, 'attempts'),
            'refundedCount' => (int) $status(PaymentStatus::Refunded, 'attempts'),
            'pendingCount' => (int) $status(PaymentStatus::Pending, 'attempts'),
            'donations' => $paidSum(Donation::query()),
            'bookings' => $paidSum(EventBooking::query()),
            'voteOrders' => $paidSum(VoteOrder::query()),
            'byType' => $byType,
            'dailyChart' => $this->chart('bar', $daily->pluck('day')->all(), [
                ['label' => 'Revenue', 'data' => $daily->pluck('revenue')->map(fn ($v) => round((float) $v, 2))->all()],
            ]),
            'typeChart' => $this->chart('doughnut', $byType->pluck('label')->all(), [
                ['label' => 'Revenue', 'data' => $byType->pluck('total')->map(fn ($v) => round((float) $v, 2))->all()],
            ]),
        ];
    }

    private function votersReport(Request $request): array
    {
        $totals = User::query()
            ->voters()
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END), 0) as verified')
            ->toBase()
            ->first();

        $newInRange = User::query()->voters()->tap(fn ($q) => $this->applyDateRange($q, $request))->count();

        $votedInRange = $this->validVotes($request)
            ->whereNotNull('votes.user_id')
            ->distinct()
            ->count('votes.user_id');

        $daily = User::query()
            ->voters()
            ->tap(fn ($q) => $this->applyDateRange($q, $request))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->toBase()
            ->get();

        return [
            'total' => (int) $totals->total,
            'verified' => (int) $totals->verified,
            'newInRange' => $newInRange,
            'votedInRange' => $votedInRange,
            'dailyChart' => $this->chart('line', $daily->pluck('day')->all(), [
                ['label' => 'New registrations', 'data' => $daily->pluck('total')->map(fn ($v) => (int) $v)->all()],
            ]),
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Exports */
    /* ------------------------------------------------------------------ */

    private function exportVotesByNominee(Request $request, bool $allowed): StreamedResponse
    {
        abort_unless($allowed, 403);
        $query = $this->votesByNomineeQuery($request);

        return $this->csv('votes-by-nominee', ['Nominee ID', 'Nominee', 'Category', 'Free votes', 'Paid votes', 'Adjustments', 'Total valid votes'], function () use ($query) {
            foreach ($query->cursor() as $row) {
                yield [$row->id, $row->nominee, $row->category, (int) $row->free_votes, (int) $row->paid_votes, (int) $row->adjustments, (int) $row->total];
            }
        });
    }

    private function exportVotesByCategory(Request $request, bool $allowed): StreamedResponse
    {
        abort_unless($allowed, 403);
        $query = $this->votesByCategoryQuery($request);

        return $this->csv('votes-by-category', ['Category ID', 'Category', 'Event', 'Free votes', 'Paid votes', 'Adjustments', 'Total valid votes'], function () use ($query) {
            foreach ($query->cursor() as $row) {
                yield [$row->id, $row->category, $row->event, (int) $row->free_votes, (int) $row->paid_votes, (int) $row->adjustments, (int) $row->total];
            }
        });
    }

    private function exportRevenueDaily(Request $request, bool $allowed): StreamedResponse
    {
        abort_unless($allowed, 403);
        $query = $this->dailyRevenueQuery($request);
        $currency = config('awards.currency');

        return $this->csv('revenue-daily', ['Date', 'Successful payments', 'Revenue', 'Refunded payments', 'Refunded amount', 'Failed/abandoned payments', 'Currency'], function () use ($query, $currency) {
            foreach ($query->cursor() as $row) {
                yield [$row->day, (int) $row->successful, round((float) $row->revenue, 2), (int) $row->refunds, round((float) $row->refunded, 2), (int) $row->failed, $currency];
            }
        });
    }

    private function exportVoters(Request $request, bool $allowed): StreamedResponse
    {
        abort_unless($allowed, 403);

        $query = User::query()
            ->voters()
            ->select(['id', 'name', 'email', 'phone', 'email_verified_at', 'status', 'created_at', 'last_login_at'])
            ->withSum(['votes as total_valid_votes' => fn ($q) => $q->where('status', Vote::STATUS_VALID)], 'votes')
            ->tap(fn ($q) => $this->applyDateRange($q, $request));

        return $this->csv('voters', ['ID', 'Name', 'Email', 'Phone', 'Verified', 'Status', 'Registered at', 'Last login at', 'Total valid votes'], function () use ($query) {
            foreach ($query->lazyById(1000, 'users.id', 'id') as $u) {
                yield [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->phone,
                    $u->email_verified_at ? 'yes' : 'no',
                    $u->status,
                    $u->created_at?->format('Y-m-d H:i:s'),
                    $u->last_login_at?->format('Y-m-d H:i:s'),
                    (int) $u->total_valid_votes,
                ];
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /* Query builders */
    /* ------------------------------------------------------------------ */

    /** @return array{voting: bool, financial: bool, voters: bool} */
    private function permissions(Request $request): array
    {
        $user = $request->user();

        return [
            'voting' => $user->can('reports.voting'),
            'financial' => $user->can('reports.financial'),
            'voters' => $user->can('reports.voters'),
        ];
    }

    /** Valid votes with the event + date filters applied. */
    private function validVotes(Request $request): Builder
    {
        return Vote::query()
            ->where('votes.status', Vote::STATUS_VALID)
            ->when($request->integer('event'), fn ($q, $event) => $q->where('votes.award_event_id', $event))
            ->tap(fn ($q) => $this->applyDateRange($q, $request, 'votes.created_at'));
    }

    /** SUM per vote type, as SQL fragments usable on MySQL and SQLite. */
    private function typeSums(): string
    {
        return sprintf(
            "SUM(CASE WHEN votes.vote_type = '%s' THEN votes.votes ELSE 0 END) as free_votes, ".
            "SUM(CASE WHEN votes.vote_type = '%s' THEN votes.votes ELSE 0 END) as paid_votes, ".
            "SUM(CASE WHEN votes.vote_type = '%s' THEN votes.votes ELSE 0 END) as adjustments, ".
            'SUM(votes.votes) as total',
            VoteType::Free->value,
            VoteType::Paid->value,
            VoteType::AdminAdjustment->value,
        );
    }

    private function votesByNomineeQuery(Request $request): \Illuminate\Database\Query\Builder
    {
        return $this->validVotes($request)
            ->join('nominees', 'nominees.id', '=', 'votes.nominee_id')
            ->join('categories', 'categories.id', '=', 'votes.category_id')
            ->selectRaw('nominees.id as id, nominees.name as nominee, categories.name as category, '.$this->typeSums())
            ->groupBy('nominees.id', 'nominees.name', 'categories.id', 'categories.name')
            ->orderByDesc('total')
            ->toBase();
    }

    private function votesByCategoryQuery(Request $request): \Illuminate\Database\Query\Builder
    {
        return $this->validVotes($request)
            ->join('categories', 'categories.id', '=', 'votes.category_id')
            ->join('award_events', 'award_events.id', '=', 'votes.award_event_id')
            ->selectRaw('categories.id as id, categories.name as category, award_events.name as event, '.$this->typeSums())
            ->groupBy('categories.id', 'categories.name', 'award_events.id', 'award_events.name')
            ->orderByDesc('total')
            ->toBase();
    }

    /** Payment attempts with the event + date filters applied (event via the payable). */
    private function payments(Request $request): Builder
    {
        return Payment::query()
            ->when($request->integer('event'), fn ($q, $event) => $q->whereHasMorph(
                'payable',
                [VoteOrder::class, Donation::class, EventBooking::class],
                fn ($q) => $q->where('award_event_id', $event),
            ))
            ->tap(fn ($q) => $this->applyDateRange($q, $request));
    }

    private function dailyRevenueQuery(Request $request): \Illuminate\Database\Query\Builder
    {
        return $this->payments($request)
            ->selectRaw(
                'DATE(created_at) as day, '.
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as successful, '.
                'COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as revenue, '.
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as refunds, '.
                'COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as refunded, '.
                'SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as failed',
                [
                    PaymentStatus::Success->value, PaymentStatus::Success->value,
                    PaymentStatus::Refunded->value, PaymentStatus::Refunded->value,
                    PaymentStatus::Failed->value, PaymentStatus::Abandoned->value,
                ],
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('day')
            ->toBase();
    }

    /** JSON for <canvas data-chart> (rendered by resources/js/admin.js). */
    private function chart(string $type, array $labels, array $datasets, bool $horizontal = false): string
    {
        return (string) json_encode(array_filter([
            'type' => $type,
            'labels' => array_values($labels),
            'datasets' => $datasets,
            'horizontal' => $horizontal ?: null,
        ], fn ($v) => $v !== null));
    }
}
