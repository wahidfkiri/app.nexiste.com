<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionExpiryReminderMail;
use App\Models\TenantSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionExpiryReminders extends Command
{
    protected $signature = 'subscriptions:remind-expiry {--days=7 : Nombre de jours avant expiration}';

    protected $description = 'Envoie un e-mail de rappel aux abonnements qui expirent bientôt (par défaut J-7).';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $count = 0;

        TenantSubscription::query()
            ->whereIn('status', ['active', 'trialing'])
            ->whereNull('reminder_sent_at')
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays($days)])
            ->with('tenant')
            ->chunkById(100, function ($subscriptions) use (&$count) {
                foreach ($subscriptions as $subscription) {
                    $email = $subscription->tenant?->email;

                    try {
                        if ($email) {
                            Mail::to($email)->send(new SubscriptionExpiryReminderMail($subscription));
                            $count++;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Subscription reminder failed: ' . $e->getMessage());
                    }

                    // Marqué même si l'e-mail échoue, pour éviter le spam quotidien.
                    $subscription->forceFill(['reminder_sent_at' => now()])->save();
                }
            });

        $this->info("Rappels d'expiration envoyés : {$count}");

        return self::SUCCESS;
    }
}
