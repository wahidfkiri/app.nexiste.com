<?php

namespace App\Notifications;

use App\Models\Draft;
use App\Services\DraftService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Notifications\Notification;

class DraftSavedNotification extends Notification
{
    use Queueable;
    use SerializesModels;

    public function __construct(protected Draft $draft) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'notification_kind' => 'draft_saved',
            'draft_id' => (int) $this->draft->id,
            'type' => (string) $this->draft->type,
            'route' => $this->draft->route,
            'title' => 'Brouillon sauvegarde',
            'message' => sprintf(
                'Votre %s en cours a ete sauvegarde automatiquement. Reprenez la creation quand vous voulez.',
                $this->typeLabel()
            ),
            'action_url' => app(DraftService::class)->resolveResumeUrl($this->draft),
            'updated_at' => optional($this->draft->updated_at)?->toIso8601String(),
        ];
    }

    protected function typeLabel(): string
    {
        return (string) (config('drafts.type_labels.' . $this->draft->type) ?: $this->draft->type);
    }
}
