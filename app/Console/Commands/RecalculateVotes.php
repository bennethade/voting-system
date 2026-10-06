<?php

namespace App\Console\Commands;

use App\Models\AwardEvent;
use App\Services\VotingService;
use Illuminate\Console\Command;

class RecalculateVotes extends Command
{
    protected $signature = 'votes:recalculate
        {--event= : Only recalculate nominees of this event (slug or id)}
        {--dry-run : Report drift without changing anything}';

    protected $description = 'Rebuild nominees.total_votes from the vote ledger (valid votes only)';

    public function handle(VotingService $voting): int
    {
        $event = null;

        if ($ref = $this->option('event')) {
            $event = AwardEvent::where('slug', $ref)->orWhere('id', $ref)->first();

            if (! $event) {
                $this->error("Event [{$ref}] not found.");

                return self::FAILURE;
            }
        }

        $dryRun = (bool) $this->option('dry-run');
        $drifted = $voting->recalculate($event, $dryRun);

        if ($drifted === []) {
            $this->info('All nominee totals match the ledger.');

            return self::SUCCESS;
        }

        $this->table(
            ['Nominee ID', 'Name', 'Cached total', 'Ledger total', 'Difference'],
            array_map(fn ($r) => [$r['nominee_id'], $r['name'], $r['cached'], $r['actual'], $r['actual'] - $r['cached']], $drifted),
        );

        $this->{$dryRun ? 'warn' : 'info'}(
            count($drifted).' nominee(s) '.($dryRun ? 'have drifted (dry run, nothing changed).' : 'corrected.')
        );

        return self::SUCCESS;
    }
}
