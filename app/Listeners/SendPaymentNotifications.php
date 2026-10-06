<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Mail\BookingConfirmationMail;
use App\Mail\DonationReceiptMail;
use App\Mail\PaymentReceiptMail;
use App\Models\Donation;
use App\Models\EventBooking;
use App\Models\VoteOrder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendPaymentNotifications implements ShouldQueue
{
    public function handle(PaymentSucceeded $event): void
    {
        $payment = $event->payment->loadMissing('payable');
        $payable = $payment->payable;

        $mail = match (true) {
            $payable instanceof VoteOrder => new PaymentReceiptMail($payment, $payable->loadMissing('nominee', 'category')),
            $payable instanceof EventBooking => new BookingConfirmationMail($payable->loadMissing('eventPackage', 'awardEvent')),
            $payable instanceof Donation => new DonationReceiptMail($payable),
            default => null,
        };

        if ($mail) {
            Mail::to($payment->email)->send($mail);
        }
    }
}
