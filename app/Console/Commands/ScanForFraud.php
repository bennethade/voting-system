<?php

namespace App\Console\Commands;

use App\Services\FraudDetectionService;
use Illuminate\Console\Command;

class ScanForFraud extends Command
{
    protected $signature = 'fraud:scan {--hours=24 : Look-back window}';

    protected $description = 'Scan recent voting activity and raise fraud flags for admin review';

    public function handle(FraudDetectionService $fraud): int
    {
        $count = $fraud->scan((int) $this->option('hours'));

        $this->info("{$count} new flag(s) raised.");

        return self::SUCCESS;
    }
}
