<?php

namespace App\Mail;

use App\Enums\VoteType;
use App\Http\Controllers\ReceiptController;
use App\Models\Vote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VoteConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Vote $vote) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your vote for {$this->vote->nominee->name} has been recorded");
    }

    public function content(): Content
    {
        $order = $this->vote->voteOrder;

        return new Content(markdown: 'emails.votes.confirmation', with: [
            'vote' => $this->vote,
            'isPaid' => $this->vote->vote_type === VoteType::Paid,
            'reference' => $order?->reference ?? 'FREE-'.$this->vote->id,
            'receiptUrl' => $order ? ReceiptController::voteUrl($order) : null,
            'nomineeUrl' => route('nominees.show', $this->vote->nominee),
        ]);
    }
}
