<?php

namespace App\Listeners;

use App\Events\VoteRecorded;
use App\Mail\VoteConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendVoteConfirmation implements ShouldQueue
{
    public function handle(VoteRecorded $event): void
    {
        $vote = $event->vote->loadMissing('nominee', 'category', 'user', 'voteOrder');
        $email = $vote->voteOrder?->voter_email ?? $vote->user?->email;

        if ($email) {
            Mail::to($email)->send(new VoteConfirmationMail($vote));
        }
    }
}
