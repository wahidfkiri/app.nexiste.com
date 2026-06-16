<?php

namespace Vendor\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Vendor\User\Models\UserInvitation;

class UserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public UserInvitation $invitation)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $invitedBy = $this->invitation->invitedBy?->name ?? config('app.name');
        $tenantName = $this->invitation->tenant?->name ?? config('app.name');
        $role = config("user.tenant_roles.{$this->invitation->role_in_tenant}", $this->invitation->role_in_tenant);
        $acceptUrl = route('users.accept', $this->invitation->token);
        $expiresDays = (int) config('user.invitation.expire_days', 7);

        return (new MailMessage)
            ->subject(__('user::users.mail.subject', ['tenant' => $tenantName]))
            ->greeting(__('user::users.mail.greeting'))
            ->line(__('user::users.mail.line_1', [
                'invitedBy' => $invitedBy,
                'tenant' => $tenantName,
                'role' => $role,
            ]))
            ->action(__('user::users.mail.action'), $acceptUrl)
            ->line(__('user::users.mail.line_2', ['days' => $expiresDays]))
            ->line(__('user::users.mail.line_3'));
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'user_invitation',
            'email' => $this->invitation->email,
            'invited_by' => $this->invitation->invited_by,
            'role' => $this->invitation->role_in_tenant,
            'expires_at' => $this->invitation->expires_at,
        ];
    }
}