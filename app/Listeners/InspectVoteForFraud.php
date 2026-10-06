<?php

namespace App\Listeners;

use App\Events\VoteRecorded;
use App\Services\FraudDetectionService;
use Illuminate\Contracts\Queue\ShouldQueue;

/** Runs on the queue so detection never slows down or blocks voting. */
class InspectVoteForFraud implements ShouldQueue
{
    public function __construct(private readonly FraudDetectionService $fraud) {}

    public function handle(VoteRecorded $event): void
    {
        $this->fraud->inspectVote($event->vote);
    }
}
