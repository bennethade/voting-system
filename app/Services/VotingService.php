<?php

namespace App\Services;

use App\Enums\FreeVoteScope;
use App\Enums\VoteType;
use App\Events\VoteRecorded;
use App\Exceptions\VotingException;
use App\Models\AwardEvent;
use App\Models\Category;
use App\Models\Nominee;
use App\Models\User;
use App\Models\Vote;
use App\Models\VoteOrder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * The only place votes are written. Every change to a nominee's total goes
 * through here, as a ledger row plus a counter update in one transaction.
 * Usable from web controllers, an API, admin screens and the CLI alike.
 */
class VotingService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly LeaderboardService $leaderboard,
    ) {}

    /**
     * Cast the user's free vote for a nominee.
     *
     * The free-vote rule is encoded in `free_vote_key`, which has a unique
     * index: however many concurrent requests arrive (double clicks,
     * refreshes, scripted replays), at most one row per key can ever exist.
     *
     * @throws VotingException
     */
    public function castFreeVote(User $user, Nominee $nominee, ?string $ip = null, ?string $userAgent = null): Vote
    {
        [$category, $event] = $this->resolveVotable($nominee);

        if (! $event->setting('free_voting_enabled')) {
            throw new VotingException('Free voting is not available for this event.');
        }

        if (! $user->isActive()) {
            throw new VotingException('Your account is not active. Please contact support.');
        }

        if ($event->setting('free_vote_requires_verification') && ! $user->hasVerifiedEmail()) {
            throw new VotingException('Please verify your email address before casting your free vote.');
        }

        $key = $this->freeVoteKey($event, $user, $category);

        // Cheap pre-check for a friendly message; the unique index is the real guard.
        if (Vote::where('free_vote_key', $key)->exists()) {
            throw new VotingException($this->alreadyVotedMessage($event));
        }

        try {
            $vote = DB::transaction(function () use ($user, $nominee, $category, $event, $key, $ip, $userAgent) {
                $vote = Vote::create([
                    'award_event_id' => $event->id,
                    'category_id' => $category->id,
                    'nominee_id' => $nominee->id,
                    'user_id' => $user->id,
                    'vote_type' => VoteType::Free,
                    'votes' => 1,
                    'status' => Vote::STATUS_VALID,
                    'free_vote_key' => $key,
                    'ip_address' => $ip,
                    'user_agent' => $this->truncate($userAgent),
                    'metadata' => ['scope' => $event->freeVoteScope()->value],
                ]);

                $this->incrementTotal($nominee->id, 1);
                VoteRecorded::dispatch($vote);

                return $vote;
            });
        } catch (UniqueConstraintViolationException) {
            throw new VotingException($this->alreadyVotedMessage($event));
        }

        $this->leaderboard->forget($event);

        return $vote;
    }

    /**
     * Credit the votes bought by a paid order. Must be called inside the
     * payment transaction with the order row locked (see PaymentService).
     * The unique index on votes.vote_order_id makes double-crediting
     * impossible even if that contract is ever broken.
     */
    public function creditPaidOrder(VoteOrder $order): Vote
    {
        $vote = Vote::create([
            'award_event_id' => $order->award_event_id,
            'category_id' => $order->category_id,
            'nominee_id' => $order->nominee_id,
            'user_id' => $order->user_id,
            'vote_order_id' => $order->id,
            'vote_type' => VoteType::Paid,
            'votes' => $order->votes,
            'status' => Vote::STATUS_VALID,
            'ip_address' => $order->ip_address,
            'user_agent' => $order->user_agent,
            'metadata' => [
                'order_reference' => $order->reference,
                'payment_reference' => $order->payment_reference,
                'amount' => (string) $order->amount,
                'currency' => $order->currency,
            ],
        ]);

        $this->incrementTotal($order->nominee_id, $order->votes);
        VoteRecorded::dispatch($vote);

        DB::afterCommit(fn () => $this->leaderboard->forgetById($order->award_event_id));

        return $vote;
    }

    /**
     * Record an administrative correction as a new signed ledger row. The
     * original votes are never edited.
     *
     * @throws VotingException
     */
    public function adjust(Nominee $nominee, int $adjustment, string $reason, User $admin): Vote
    {
        if ($adjustment === 0) {
            throw new VotingException('The adjustment must be a non-zero number of votes.');
        }

        $category = $nominee->category;
        $event = $category->awardEvent;

        if ($event->isResultsLocked() && ! $admin->isSuperAdmin()) {
            throw new VotingException('Results for this event are locked. Only a super administrator can record corrections.');
        }

        return DB::transaction(function () use ($nominee, $category, $event, $adjustment, $reason, $admin) {
            $locked = Nominee::whereKey($nominee->id)->lockForUpdate()->firstOrFail();
            $oldTotal = $locked->total_votes;
            $newTotal = $oldTotal + $adjustment;

            if ($newTotal < 0) {
                throw new VotingException("This adjustment would make the total negative (current total: {$oldTotal}).");
            }

            $vote = Vote::create([
                'award_event_id' => $event->id,
                'category_id' => $category->id,
                'nominee_id' => $nominee->id,
                'vote_type' => VoteType::AdminAdjustment,
                'votes' => $adjustment,
                'status' => Vote::STATUS_VALID,
                'admin_user_id' => $admin->id,
                'reason' => $reason,
                'ip_address' => request()?->ip(),
                'metadata' => [
                    'old_total' => $oldTotal,
                    'adjustment' => $adjustment,
                    'new_total' => $newTotal,
                    'results_locked' => $event->isResultsLocked(),
                ],
            ]);

            $this->incrementTotal($nominee->id, $adjustment);

            $this->audit->log(
                'votes.adjusted',
                sprintf('Adjusted %s by %+d votes (%d → %d): %s', $nominee->name, $adjustment, $oldTotal, $newTotal, $reason),
                $vote,
                ['total_votes' => $oldTotal],
                ['total_votes' => $newTotal, 'adjustment' => $adjustment, 'reason' => $reason],
            );

            DB::afterCommit(fn () => $this->leaderboard->forget($event));

            return $vote;
        });
    }

    /**
     * Mark a ledger row invalid and remove its effect from the counter.
     *
     * @throws VotingException
     */
    public function invalidate(Vote $vote, User $admin, string $reason): Vote
    {
        $event = $vote->awardEvent;

        if ($event->isResultsLocked() && ! $admin->isSuperAdmin()) {
            throw new VotingException('Results for this event are locked. Only a super administrator can invalidate votes.');
        }

        return DB::transaction(function () use ($vote, $admin, $reason, $event) {
            $locked = Vote::whereKey($vote->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isValid()) {
                return $locked; // already invalidated: idempotent
            }

            $locked->forceFill([
                'status' => Vote::STATUS_INVALIDATED,
                'invalidated_at' => now(),
                'invalidated_by' => $admin->id,
                'metadata' => array_merge($locked->metadata ?? [], ['invalidation_reason' => $reason]),
            ])->save();

            $this->incrementTotal($locked->nominee_id, -$locked->votes);

            $this->audit->log(
                'votes.invalidated',
                "Invalidated {$locked->votes} {$locked->vote_type->value} vote(s) #{$locked->id}: {$reason}",
                $locked,
                ['status' => Vote::STATUS_VALID],
                ['status' => Vote::STATUS_INVALIDATED, 'reason' => $reason],
            );

            DB::afterCommit(fn () => $this->leaderboard->forget($event));

            return $locked;
        });
    }

    /**
     * Rebuild nominees.total_votes from the ledger.
     *
     * @return array<int, array{nominee_id:int, name:string, cached:int, actual:int}> nominees that drifted
     */
    public function recalculate(?AwardEvent $event = null, bool $dryRun = false): array
    {
        $drifted = [];

        $query = Nominee::query()
            ->select('nominees.id', 'nominees.name', 'nominees.total_votes')
            ->selectSub(
                Vote::query()->valid()->whereColumn('votes.nominee_id', 'nominees.id')->selectRaw('COALESCE(SUM(votes), 0)'),
                'ledger_total',
            )
            ->when($event, fn ($q) => $q->whereIn('category_id', $event->categories()->select('id')));

        $query->orderBy('nominees.id')->chunk(500, function ($nominees) use (&$drifted, $dryRun) {
            foreach ($nominees as $nominee) {
                $actual = (int) $nominee->ledger_total;

                if ($actual !== (int) $nominee->total_votes) {
                    $drifted[] = [
                        'nominee_id' => $nominee->id,
                        'name' => $nominee->name,
                        'cached' => (int) $nominee->total_votes,
                        'actual' => $actual,
                    ];

                    if (! $dryRun) {
                        Nominee::whereKey($nominee->id)->update(['total_votes' => $actual]);
                    }
                }
            }
        });

        if (! $dryRun) {
            $this->leaderboard->flushAll();
        }

        return $drifted;
    }

    /** Whether this user can still cast a free vote in the nominee's category. */
    public function hasFreeVoteAvailable(User $user, Category $category): bool
    {
        $event = $category->awardEvent;

        return $event->setting('free_voting_enabled')
            && ! Vote::where('free_vote_key', $this->freeVoteKey($event, $user, $category))->exists();
    }

    public function freeVoteKey(AwardEvent $event, User $user, Category $category): string
    {
        $base = "e{$event->id}:u{$user->id}";
        $day = now()->format('Ymd');

        return match ($event->freeVoteScope()) {
            FreeVoteScope::Event => $base,
            FreeVoteScope::EventDaily => "{$base}:d{$day}",
            FreeVoteScope::Category => "{$base}:c{$category->id}",
            FreeVoteScope::CategoryDaily => "{$base}:c{$category->id}:d{$day}",
        };
    }

    /**
     * Check a nominee can receive votes right now.
     *
     * @return array{0: Category, 1: AwardEvent}
     *
     * @throws VotingException
     */
    public function resolveVotable(Nominee $nominee): array
    {
        $nominee->loadMissing('category.awardEvent');
        $category = $nominee->category;
        $event = $category?->awardEvent;

        if (! $event || $nominee->status !== 'active' || $category->status !== 'active') {
            throw new VotingException('This nominee is not available for voting.');
        }

        if ($reason = $event->votingClosedReason()) {
            throw new VotingException($reason);
        }

        return [$category, $event];
    }

    private function incrementTotal(int $nomineeId, int $by): void
    {
        // Atomic `total_votes = total_votes + ?` — safe under concurrency.
        Nominee::whereKey($nomineeId)->increment('total_votes', $by);
    }

    private function alreadyVotedMessage(AwardEvent $event): string
    {
        return match ($event->freeVoteScope()) {
            FreeVoteScope::Event => 'You have already used your free vote for this event.',
            FreeVoteScope::EventDaily => 'You have already used your free vote today. Come back tomorrow!',
            FreeVoteScope::Category => 'You have already used your free vote in this category.',
            FreeVoteScope::CategoryDaily => 'You have already used your free vote in this category today. Come back tomorrow!',
        };
    }

    private function truncate(?string $value, int $length = 512): ?string
    {
        return $value === null ? null : mb_substr($value, 0, $length);
    }
}
