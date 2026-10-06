<?php

namespace App\Mail;

use App\Http\Controllers\ReceiptController;
use App\Models\Payment;
use App\Models\VoteOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment, public VoteOrder $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Payment successful — '.money($this->payment->amount, $this->payment->currency));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.payments.receipt', with: [
            'payment' => $this->payment,
            'order' => $this->order,
            'receiptUrl' => ReceiptController::voteUrl($this->order),
        ]);
    }
}
