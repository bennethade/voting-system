<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\VotingException;
use App\Models\Nominee;
use App\Models\User;
use App\Models\VoteOrder;
use App\Models\VotingPackage;

/**
 * Creates paid-vote orders. The amount and vote count are copied from the
 * server-side package record: nothing price-related is read from the request.
 */
class OrderService
{
    public function __construct(
        private readonly VotingService $voting,
        private readonly ReferenceGenerator $references,
    ) {}

    /**
     * @param  array{name?: ?string, email: string, phone?: ?string}  $voter
     *
     * @throws VotingException
     */
    public function createVoteOrder(
        Nominee $nominee,
        int $packageId,
        array $voter,
        ?User $user,
        ?string $ip = null,
        ?string $userAgent = null,
    ): VoteOrder {
        [$category, $event] = $this->voting->resolveVotable($nominee);

        if (! $event->setting('paid_voting_enabled')) {
            throw new VotingException('Paid voting is not available for this event.');
        }

        if ($event->setting('paid_vote_requires_login') && ! $user) {
            throw new VotingException('Please sign in to purchase votes.');
        }

        if ($user && ! $user->isActive()) {
            throw new VotingException('Your account is not active. Please contact support.');
        }

        $package = VotingPackage::query()
            ->whereKey($packageId)
            ->where('award_event_id', $event->id)
            ->active()
            ->first();

        if (! $package) {
            throw new VotingException('The selected voting package is not available.');
        }

        return VoteOrder::create([
            'reference' => $this->references->generate('VOTE', VoteOrder::class),
            'award_event_id' => $event->id,
            'category_id' => $category->id,
            'nominee_id' => $nominee->id,
            'voting_package_id' => $package->id,
            'user_id' => $user?->id,
            'voter_name' => $voter['name'] ?? $user?->name,
            'voter_email' => $user?->email ?? $voter['email'],
            'voter_phone' => $voter['phone'] ?? $user?->phone,
            'amount' => $package->amount,
            'votes' => $package->votes,
            'currency' => $event->currency(),
            'payment_provider' => 'paystack',
            'status' => OrderStatus::Pending,
            'ip_address' => $ip,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 512) : null,
            'metadata' => ['package_name' => $package->name],
        ]);
    }
}
