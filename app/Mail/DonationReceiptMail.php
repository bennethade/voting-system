<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonationReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Donation $donation) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Thank you for your donation of '.money($this->donation->amount, $this->donation->currency));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.donations.receipt', with: ['donation' => $this->donation]);
    }
}
