<?php

namespace App\Mail;

use App\Models\TenantSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiryReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TenantSubscription $subscription)
    {
    }

    public function build(): self
    {
        return $this
            ->subject(__('billing.reminder.subject', ['app' => config('app.name', 'CRM')]))
            ->view('emails.subscription-reminder');
    }
}
