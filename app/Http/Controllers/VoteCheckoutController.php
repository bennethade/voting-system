<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\VoteCheckoutRequest;
use App\Models\Nominee;
use App\Models\VoteOrder;
use App\Services\OrderService;
use App\Services\VotingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Paid voting: choose package → review (server-calculated amount) →
 * explicit confirm (CheckoutController::pay) → Paystack.
 */
class VoteCheckoutController extends Controller
{
    public function create(Request $request, Nominee $nominee, VotingService $voting): View
    {
        [$category, $event] = $voting->resolveVotable($nominee);

        abort_unless($event->setting('paid_voting_enabled'), 404);

        return view('voting.checkout', [
            'nominee' => $nominee,
            'category' => $category,
            'event' => $event,
            'packages' => $event->activePackages()->get(),
            'selected' => (int) $request->query('package'),
            'requiresLogin' => $event->setting('paid_vote_requires_login') && ! $request->user(),
        ]);
    }

    public function store(VoteCheckoutRequest $request, Nominee $nominee, OrderService $orders): RedirectResponse
    {
        $order = $orders->createVoteOrder(
            $nominee,
            (int) $request->validated('package_id'),
            $request->safe()->only(['name', 'email', 'phone']),
            $request->user(),
            $request->ip(),
            $request->userAgent(),
        );

        return redirect(self::reviewUrl($order));
    }

    public function show(VoteOrder $voteOrder): View|RedirectResponse
    {
        $voteOrder->load('nominee', 'category', 'awardEvent');

        if ($voteOrder->status === OrderStatus::Paid) {
            return redirect(ReceiptController::voteUrl($voteOrder));
        }

        return view('voting.review', [
            'order' => $voteOrder,
            'payUrl' => URL::temporarySignedRoute('checkout.pay', now()->addHours(2), [
                'type' => 'vote_order',
                'reference' => $voteOrder->reference,
            ]),
            'votingOpen' => $voteOrder->awardEvent->isVotingOpen(),
        ]);
    }

    public static function reviewUrl(VoteOrder $order): string
    {
        return URL::temporarySignedRoute('votes.checkout.review', now()->addHours(2), ['voteOrder' => $order->reference]);
    }
}
