<?php

namespace App\Services;

use App\Enums\FraudStatus;
use App\Enums\PaymentStatus;
use App\Enums\VoteType;
use App\Models\FraudFlag;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Heuristic abuse detection. It only ever *raises flags* for a human to
 * review; it never removes or invalidates votes on its own.
 *
 * Each flag carries a signature (pattern + subject + time bucket) with a
 * unique index, so the same pattern is reported once per window no matter
 * how many votes trigger it.
 */
class FraudDetectionService
{
    public function __construct(
        private readonly VotingService $voting,
        private readonly AuditLogger $audit,
    ) {}

    public function inspectVote(Vote $vote): void
    {
        $limits = config('awards.fraud');
        $now = now();

        if ($vote->ip_address && $vote->vote_type === VoteType::Free) {
            $hourStart = $now->copy()->startOfHour();
            $count = Vote::where('award_event_id', $vote->award_event_id)
                ->where('vote_type', VoteType::Free->value)
                ->where('ip_address', $vote->ip_address)
                ->where('created_at', '>=', $hourStart)
                ->count();

            if ($count > $limits['ip_free_votes_per_hour']) {
                $this->flag([
                    'type' => 'ip_repeat',
                    'severity' => 'medium',
                    'award_event_id' => $vote->award_event_id,
                    'ip_address' => $vote->ip_address,
                    'description' => "{$count} free votes from IP {$vote->ip_address} within the hour.",
                    'signature' => "ip_repeat:{$vote->award_event_id}:{$vote->ip_address}:".$now->format('YmdH'),
                    'metadata' => ['count' => $count, 'window_start' => $hourStart->toIso8601String(), 'window_end' => $hourStart->copy()->endOfHour()->toIso8601String()],
                ]);
            }
        }

        if ($vote->ip_address && $vote->user_id) {
            $dayStart = $now->copy()->startOfDay();
            $accounts = Vote::where('award_event_id', $vote->award_event_id)
                ->where('ip_address', $vote->ip_address)
                ->where('created_at', '>=', $dayStart)
                ->whereNotNull('user_id')
                ->distinct()
                ->count('user_id');

            if ($accounts > $limits['accounts_per_ip_per_day']) {
                $this->flag([
                    'type' => 'multi_account',
                    'severity' => 'high',
                    'award_event_id' => $vote->award_event_id,
                    'ip_address' => $vote->ip_address,
                    'description' => "{$accounts} different accounts voted from IP {$vote->ip_address} today.",
                    'signature' => "multi_account:{$vote->award_event_id}:{$vote->ip_address}:".$now->format('Ymd'),
                    'metadata' => ['accounts' => $accounts, 'window_start' => $dayStart->toIso8601String(), 'window_end' => $dayStart->copy()->endOfDay()->toIso8601String()],
                ]);
            }
        }

        $bucket = intdiv($now->timestamp, 600);
        $recent = (int) Vote::valid()
            ->where('nominee_id', $vote->nominee_id)
            ->where('created_at', '>=', $now->copy()->subMinutes(10))
            ->sum('votes');

        if ($recent > $limits['nominee_votes_spike_per_10_min']) {
            $this->flag([
                'type' => 'vote_spike',
                'severity' => 'medium',
                'award_event_id' => $vote->award_event_id,
                'nominee_id' => $vote->nominee_id,
                'description' => "{$recent} votes for nominee #{$vote->nominee_id} in the last 10 minutes.",
                'signature' => "vote_spike:{$vote->nominee_id}:{$bucket}",
                'metadata' => ['votes' => $recent],
            ]);
        }

        if ($vote->vote_type === VoteType::Paid && $vote->vote_order_id) {
            $this->inspectOrder($vote->voteOrder);
        }
    }

    public function inspectOrder(VoteOrder $order): void
    {
        $limits = config('awards.fraud');

        if ($order->votes >= $limits['single_order_votes_threshold']) {
            $this->flag([
                'type' => 'high_activity',
                'severity' => 'low',
                'award_event_id' => $order->award_event_id,
                'nominee_id' => $order->nominee_id,
                'email' => $order->voter_email,
                'user_id' => $order->user_id,
                'ip_address' => $order->ip_address,
                'flaggable_type' => $order->getMorphClass(),
                'flaggable_id' => $order->id,
                'description' => "Single order of {$order->votes} votes ({$order->reference}).",
                'signature' => "high_order:{$order->id}",
            ]);
        }

        $orders = VoteOrder::where('voter_email', $order->voter_email)
            ->where('status', 'paid')
            ->where('paid_at', '>=', now()->subHour())
            ->count();

        if ($orders > $limits['email_paid_orders_per_hour']) {
            $this->flag([
                'type' => 'payment_pattern',
                'severity' => 'low',
                'award_event_id' => $order->award_event_id,
                'email' => $order->voter_email,
                'description' => "{$orders} paid orders from {$order->voter_email} in the last hour.",
                'signature' => "email_orders:{$order->voter_email}:".now()->format('YmdH'),
                'metadata' => ['orders' => $orders],
            ]);
        }
    }

    public function inspectFailedPayment(Payment $payment): void
    {
        $failed = Payment::where('email', $payment->email)
            ->where('status', PaymentStatus::Failed->value)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($failed > config('awards.fraud.failed_payments_per_email_per_hour')) {
            $this->flag([
                'type' => 'payment_pattern',
                'severity' => 'medium',
                'email' => $payment->email,
                'ip_address' => $payment->ip_address,
                'description' => "{$failed} failed payments from {$payment->email} in the last hour.",
                'signature' => "failed_payments:{$payment->email}:".now()->format('YmdH'),
                'metadata' => ['failed' => $failed],
            ]);
        }
    }

    public function flagAmountMismatch(Payment $payment, int $receivedSubunit): void
    {
        $this->flag([
            'type' => 'payment_pattern',
            'severity' => 'high',
            'email' => $payment->email,
            'ip_address' => $payment->ip_address,
            'flaggable_type' => $payment->getMorphClass(),
            'flaggable_id' => $payment->id,
            'description' => "Amount mismatch on {$payment->reference}: expected {$payment->amountInSubunit()}, received {$receivedSubunit} (subunits).",
            'signature' => "amount_mismatch:{$payment->id}",
        ]);
    }

    public function flagDuplicatePayment(Payment $payment): void
    {
        $this->flag([
            'type' => 'payment_pattern',
            'severity' => 'medium',
            'email' => $payment->email,
            'flaggable_type' => $payment->getMorphClass(),
            'flaggable_id' => $payment->id,
            'description' => "Payment {$payment->reference} was made for an order that was already paid. Consider refunding it.",
            'signature' => "duplicate_payment:{$payment->id}",
        ]);
    }

    /**
     * Periodic sweep (see `php artisan fraud:scan`) for patterns that are
     * cheaper to find in aggregate than per vote.
     *
     * @return int number of new flags raised
     */
    public function scan(int $hours = 24): int
    {
        $since = now()->subHours($hours);
        $limits = config('awards.fraud');
        $before = FraudFlag::count();

        Vote::query()
            ->select('award_event_id', 'ip_address', DB::raw('COUNT(DISTINCT user_id) as accounts'), DB::raw('SUM(votes) as total'))
            ->where('created_at', '>=', $since)
            ->whereNotNull('ip_address')
            ->groupBy('award_event_id', 'ip_address')
            ->havingRaw('COUNT(DISTINCT user_id) > ?', [$limits['accounts_per_ip_per_day']])
            ->get()
            ->each(fn ($row) => $this->flag([
                'type' => 'multi_account',
                'severity' => 'high',
                'award_event_id' => $row->award_event_id,
                'ip_address' => $row->ip_address,
                'description' => "{$row->accounts} accounts voted from IP {$row->ip_address} in the last {$hours}h.",
                'signature' => "scan_multi_account:{$row->award_event_id}:{$row->ip_address}:".now()->format('Ymd'),
                'metadata' => ['accounts' => (int) $row->accounts, 'votes' => (int) $row->total, 'window_start' => $since->toIso8601String(), 'window_end' => now()->toIso8601String()],
            ]));

        return FraudFlag::count() - $before;
    }

    /** Raise a flag unless one with the same signature already exists. */
    public function flag(array $attributes): ?FraudFlag
    {
        $attributes['status'] = FraudStatus::Open;

        if (empty($attributes['signature'])) {
            return FraudFlag::create($attributes);
        }

        // insertOrIgnore keeps concurrent detections from erroring on the unique signature.
        $inserted = FraudFlag::query()->insertOrIgnore([
            array_merge(
                array_fill_keys((new FraudFlag)->getFillable(), null),
                $attributes,
                [
                    'status' => FraudStatus::Open->value,
                    'metadata' => isset($attributes['metadata']) ? json_encode($attributes['metadata']) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ),
        ]);

        return $inserted ? FraudFlag::where('signature', $attributes['signature'])->first() : null;
    }

    /**
     * Apply an administrator's decision to a flag. "Invalidate" removes the
     * flagged votes from the totals (never deletes them).
     *
     * @return int number of votes invalidated
     */
    public function resolve(FraudFlag $flag, FraudStatus $status, User $admin, ?string $notes): int
    {
        $old = $flag->status;
        $invalidated = 0;

        if ($status === FraudStatus::Invalidated) {
            foreach ($this->relatedVotes($flag) as $vote) {
                $this->voting->invalidate($vote, $admin, "Fraud flag #{$flag->id}: ".($notes ?: $flag->typeLabel()));
                $invalidated++;
            }
        }

        $flag->forceFill([
            'status' => $status,
            'review_notes' => $notes,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ])->save();

        $this->audit->log(
            'fraud.resolved',
            "Fraud flag #{$flag->id} marked {$status->value}".($invalidated ? " ({$invalidated} votes invalidated)" : ''),
            $flag,
            ['status' => $old->value],
            ['status' => $status->value, 'notes' => $notes, 'invalidated' => $invalidated],
        );

        return $invalidated;
    }

    /**
     * Valid votes a flag refers to: the flagged vote/order itself, or for IP
     * patterns, the votes from that IP inside the flagged time window.
     *
     * @return Collection<int, Vote>
     */
    public function relatedVotes(FraudFlag $flag): Collection
    {
        $flaggable = $flag->flaggable;

        if ($flaggable instanceof Vote) {
            return $flaggable->isValid() ? collect([$flaggable]) : collect();
        }

        if ($flaggable instanceof VoteOrder) {
            return Vote::valid()->where('vote_order_id', $flaggable->id)->get();
        }

        if ($flag->ip_address && in_array($flag->type, ['ip_repeat', 'multi_account'], true)) {
            $start = isset($flag->metadata['window_start']) ? Carbon::parse($flag->metadata['window_start']) : $flag->created_at->copy()->subDay();
            $end = isset($flag->metadata['window_end']) ? Carbon::parse($flag->metadata['window_end']) : $flag->created_at;

            return Vote::valid()
                ->where('ip_address', $flag->ip_address)
                ->when($flag->award_event_id, fn ($q) => $q->where('award_event_id', $flag->award_event_id))
                ->whereIn('vote_type', [VoteType::Free->value])
                ->whereBetween('created_at', [$start, $end])
                ->limit(5000)
                ->get();
        }

        return collect();
    }
}
