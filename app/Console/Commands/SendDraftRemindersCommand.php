<?php

namespace App\Console\Commands;

use App\Models\Draft;
use App\Notifications\DraftReminderNotification;
use Illuminate\Console\Command;

class SendDraftRemindersCommand extends Command
{
    protected $signature = 'drafts:remind';

    protected $description = 'Send reminders for stale drafts that were not completed.';

    public function handle(): int
    {
        $thresholdHours = max(1, (int) config('drafts.reminder_after_hours', 12));
        $cooldownHours = max(1, (int) config('drafts.reminder_cooldown_hours', 24));
        $thresholdDate = now()->subHours($thresholdHours);
        $cooldownDate = now()->subHours($cooldownHours);
        $sent = 0;

        Draft::query()
            ->with('user')
            ->notExpired()
            ->where('updated_at', '<=', $thresholdDate)
            ->where(function ($query) use ($cooldownDate) {
                $query
                    ->whereNull('reminded_at')
                    ->orWhere('reminded_at', '<=', $cooldownDate);
            })
            ->orderBy('id')
            ->chunkById(100, function ($drafts) use (&$sent) {
                foreach ($drafts as $draft) {
                    if (!$draft->user) {
                        continue;
                    }

                    $draft->user->notify(new DraftReminderNotification($draft));
                    $draft->forceFill(['reminded_at' => now()])->save();
                    $sent++;
                }
            });

        $this->info(sprintf('%d rappel(s) de brouillon envoyes.', $sent));

        return self::SUCCESS;
    }
}
