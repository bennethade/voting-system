<?php

namespace App\Console\Commands;

use App\Enums\EventStatus;
use App\Models\AwardEvent;
use App\Services\LeaderboardService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Keeps event statuses in line with their voting window. Voting is already
 * refused outside the window by AwardEvent::votingClosedReason(); this just
 * makes the stored status (and admin screens) reflect reality.
 */
class SyncEventStatuses extends Command
{
    protected $signature = 'events:sync-status';

    protected $description = 'Close voting on events whose voting_end_at has passed';

    public function handle(LeaderboardService $leaderboard): int
    {
        $closed = AwardEvent::query()
            ->whereIn('status', [EventStatus::Voting->value, EventStatus::Paused->value])
            ->whereNotNull('voting_end_at')
            ->where('voting_end_at', '<=', now())
            ->get();

        foreach ($closed as $event) {
            $event->forceFill(['status' => EventStatus::Closed])->save();
            $leaderboard->forget($event);
            Log::info('Voting closed automatically', ['event' => $event->slug]);
            $this->line("Closed voting for {$event->name}");
        }

        return self::SUCCESS;
    }
}
