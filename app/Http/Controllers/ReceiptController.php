<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\EventBooking;
use App\Models\VoteOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Printable receipts. Viewable by the signed-in owner, staff with the right
 * permission, or anyone holding the signed link from the confirmation email.
 */
class ReceiptController extends Controller
{
    public function vote(Request $request, VoteOrder $voteOrder): View
    {
        $this->authorizeReceipt($request, $voteOrder->user_id, 'orders.view');
        abort_unless($voteOrder->status === OrderStatus::Paid || $voteOrder->status === OrderStatus::Refunded, 404);

        $voteOrder->load('nominee', 'category', 'awardEvent');

        return view('receipts.vote', ['order' => $voteOrder]);
    }

    public function booking(Request $request, EventBooking $eventBooking): View
    {
        $this->authorizeReceipt($request, $eventBooking->user_id, 'bookings.view');
        abort_unless($eventBooking->status === OrderStatus::Paid, 404);

        $eventBooking->load('eventPackage', 'awardEvent');

        return view('receipts.booking', ['booking' => $eventBooking]);
    }

    public static function voteUrl(VoteOrder $order): string
    {
        return URL::signedRoute('receipts.vote', ['voteOrder' => $order->reference]);
    }

    public static function bookingUrl(EventBooking $booking): string
    {
        return URL::signedRoute('receipts.booking', ['eventBooking' => $booking->reference]);
    }

    private function authorizeReceipt(Request $request, ?int $ownerId, string $staffPermission): void
    {
        $user = $request->user();

        $allowed = $request->hasValidSignature()
            || ($user && $ownerId && $user->id === $ownerId)
            || ($user && $user->can($staffPermission));

        abort_unless($allowed, 403);
    }
}
