<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('drafts:remind')->hourly();
        $schedule->command('drafts:cleanup')->dailyAt('02:30');
        $schedule->command('google-gmail:sync-realtime')->everyMinute()->withoutOverlapping();
        $schedule->command('subscriptions:remind-expiry')->dailyAt('08:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
