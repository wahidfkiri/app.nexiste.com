<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(protected string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $expireMinutes = (int) config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage)
            ->subject('Reinitialisation de votre mot de passe NexusCRM')
            ->greeting('Bonjour,')
            ->line('Nous avons recu une demande de reinitialisation de mot de passe pour votre compte NexusCRM.')
            ->action('Reinitialiser mon mot de passe', $resetUrl)
            ->line("Ce lien expirera dans {$expireMinutes} minutes.")
            ->line('Si vous n avez pas fait cette demande, vous pouvez ignorer cet email en toute securite.');
    }
}