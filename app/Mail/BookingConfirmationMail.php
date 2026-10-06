<?php

namespace App\Mail;

use App\Http\Controllers\ReceiptController;
use App\Models\EventBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public EventBooking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Booking confirmed — {$this->booking->awardEvent->name} ({$this->booking->reference})");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.bookings.confirmation', with: [
            'booking' => $this->booking,
            'receiptUrl' => ReceiptController::bookingUrl($this->booking),
        ]);
    }
}
