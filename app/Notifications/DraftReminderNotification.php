<?php

namespace App\Notifications;

use App\Models\Draft;
use App\Services\DraftService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DraftReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(protected Draft $draft) {}

    public function via(object $notifiable): array
    {
        $allowed = ['database', 'mail'];
        $channels = array_values(array_intersect(
            config('drafts.notification_channels', ['database']),
            $allowed
        ));

        return !empty($channels) ? $channels : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'notification_kind' => 'draft_reminder',
            'draft_id' => (int) $this->draft->id,
            'type' => (string) $this->draft->type,
            'route' => $this->draft->route,
            'title' => 'Rappel brouillon a finaliser',
            'message' => sprintf(
                'Vous avez un brouillon %s non termine. Voulez-vous le reprendre ?',
                $this->typeLabel()
            ),
            'action_url' => app(DraftService::class)->resolveResumeUrl($this->draft),
            'updated_at' => optional($this->draft->updated_at)?->toIso8601String(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resumeUrl = app(DraftService::class)->resolveResumeUrl($this->draft) ?: url('/dashboard');

        return (new MailMessage)
            ->subject('Brouillon CRM a reprendre')
            ->greeting('Bonjour,')
            ->line(sprintf(
                'Vous avez un brouillon %s non termine dans le CRM.',
                $this->typeLabel()
            ))
            ->line('Le formulaire a ete sauvegarde automatiquement pour que vous puissiez reprendre plus tard.')
            ->action('Reprendre le brouillon', $resumeUrl)
            ->line('Ce rappel est envoye automatiquement pour ne pas perdre votre progression.');
    }

    protected function typeLabel(): string
    {
        return (string) (config('drafts.type_labels.' . $this->draft->type) ?: $this->draft->type);
    }
}
