<?php

namespace App\Http\Controllers;

use App\Exceptions\VotingException;
use App\Models\Donation;
use App\Models\EventBooking;
use App\Models\VoteOrder;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The explicit "Confirm & pay" step. The route is signed, so only someone
 * who reached the review page for this exact payable can start a payment,
 * and the amount charged is always the stored server-side amount.
 */
class CheckoutController extends Controller
{
    private const PAYABLES = [
        'vote_order' => VoteOrder::class,
        'donation' => Donation::class,
        'event_booking' => EventBooking::class,
    ];

    public function pay(Request $request, string $type, string $reference, PaymentService $payments): RedirectResponse
    {
        $class = self::PAYABLES[$type];
        $payable = $class::where('reference', $reference)->firstOrFail();

        // Don't take money for votes that can no longer be cast.
        if ($payable instanceof VoteOrder && ($reason = $payable->awardEvent->votingClosedReason())) {
            throw new VotingException($reason);
        }

        $payment = $payments->initialize($payable, $request->user(), $request->ip());

        return redirect()->away($payment->authorization_url);
    }
}
