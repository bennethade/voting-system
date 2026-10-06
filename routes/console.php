<?php

use Illuminate\Support\Facades\Schedule;

// Run with `php artisan schedule:work` locally, or a cron entry calling
// `php artisan schedule:run` every minute in production.
Schedule::command('events:sync-status')->everyMinute()->withoutOverlapping();
Schedule::command('fraud:scan --hours=24')->hourly()->withoutOverlapping();
Schedule::command('votes:recalculate --dry-run')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=168')->daily();
