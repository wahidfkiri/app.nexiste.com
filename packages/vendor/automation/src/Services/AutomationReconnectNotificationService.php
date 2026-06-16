<?php

namespace Vendor\Automation\Services;

use App\Models\User;
use App\Notifications\AutomationSuggestionPendingNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Automation\Support\AutomationReconnectResolver;
use Vendor\Automation\Support\AutomationTenantResolver;

class AutomationReconnectNotificationService
{
    public function notifyForProvider(int $tenantId, int $userId, string $providerSlug, string $targetUrl): int
    {
        $user = User::query()->find($userId);

        if (!AutomationTenantResolver::userCanAccessTenant($user, $tenantId)) {
            return 0;
        }

        $suggestions = $this->pendingSuggestionsForProvider($tenantId, $userId, $providerSlug);
        $activeIds = $suggestions->pluck('id')->map(fn ($id) => (int) $id)->all();
        $notifications = $this->providerNotifications($user, $providerSlug);
        $bySuggestionId = $notifications->keyBy(fn (DatabaseNotification $notification) => (int) data_get($notification->data, 'suggestion_id'));

        foreach ($suggestions as $suggestion) {
            $this->upsertNotification(
                $user,
                $suggestion,
                $providerSlug,
                $targetUrl,
                'reconnected',
                $bySuggestionId->get((int) $suggestion->id)
            );
        }

        $notifications
            ->filter(fn (DatabaseNotification $notification) => !in_array((int) data_get($notification->data, 'suggestion_id'), $activeIds, true))
            ->each(fn (DatabaseNotification $notification) => $notification->delete());

        return count($activeIds);
    }

    public function syncForSuggestion(?AutomationSuggestion $suggestion): void
    {
        if (!$suggestion || !$suggestion->user_id) {
            return;
        }

        $user = $suggestion->relationLoaded('user')
            ? $suggestion->user
            : User::query()->find($suggestion->user_id);

        if (!AutomationTenantResolver::userCanAccessTenant($user, (int) $suggestion->tenant_id)) {
            return;
        }

        $notifications = $this->suggestionNotifications($user, (int) $suggestion->id);
        $providerSlug = $this->resolveSuggestionProvider($suggestion);

        if (!$suggestion->isActionable() || !$providerSlug) {
            $notifications->each(fn (DatabaseNotification $notification) => $notification->delete());
            return;
        }

        $provider = AutomationReconnectResolver::resolve($suggestion->latestFailedEvent?->last_error);
        $targetUrl = (string) ($provider['url'] ?? route('dashboard'));

        $this->upsertNotification(
            $user,
            $suggestion,
            $providerSlug,
            $targetUrl,
            'pending_reconnect',
            $notifications->first()
        );

        $notifications
            ->skip(1)
            ->each(fn (DatabaseNotification $notification) => $notification->delete());
    }

    protected function pendingSuggestionsForProvider(int $tenantId, int $userId, string $providerSlug): Collection
    {
        return AutomationSuggestion::query()
            ->with(['latestFailedEvent:automation_events.id,automation_events.triggered_by_suggestion_id,automation_events.last_error'])
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('status', AutomationSuggestion::STATUS_PENDING)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at')
            ->get()
            ->filter(fn (AutomationSuggestion $suggestion) => $this->resolveSuggestionProvider($suggestion) === $providerSlug)
            ->values();
    }

    protected function resolveSuggestionProvider(AutomationSuggestion $suggestion): ?string
    {
        $suggestion->loadMissing(['latestFailedEvent:automation_events.id,automation_events.triggered_by_suggestion_id,automation_events.last_error']);
        $latestFailedEvent = $suggestion->latestFailedEvent;

        if (!$latestFailedEvent) {
            return null;
        }

        return AutomationReconnectResolver::resolve($latestFailedEvent->last_error)['slug'] ?? null;
    }

    protected function providerNotifications(User $user, string $providerSlug): Collection
    {
        return $user->notifications()
            ->where('type', AutomationSuggestionPendingNotification::class)
            ->where('data', 'like', '%"notification_kind":"automation_suggestion_pending"%')
            ->where('data', 'like', '%"provider_slug":"' . $providerSlug . '"%')
            ->latest('updated_at')
            ->get()
            ->values();
    }

    protected function suggestionNotifications(User $user, int $suggestionId): Collection
    {
        return $user->notifications()
            ->where('type', AutomationSuggestionPendingNotification::class)
            ->where('data', 'like', '%"notification_kind":"automation_suggestion_pending"%')
            ->where('data', 'like', '%"suggestion_id":' . $suggestionId . ',%')
            ->latest('updated_at')
            ->get()
            ->values();
    }

    protected function upsertNotification(
        User $user,
        AutomationSuggestion $suggestion,
        string $providerSlug,
        string $targetUrl,
        string $resumeState,
        ?DatabaseNotification $notification = null
    ): void {
        $suggestionId = (int) $suggestion->id;
        $payload = (new AutomationSuggestionPendingNotification(
            $suggestion,
            $providerSlug,
            $this->buildResumeUrl($targetUrl, $suggestionId, $providerSlug, $resumeState),
            $resumeState
        ))->toArray($user);

        if ($notification) {
            $notification->forceFill([
                'data' => $payload,
                'read_at' => null,
            ])->save();

            return;
        }

        $user->notify(new AutomationSuggestionPendingNotification(
            $suggestion,
            $providerSlug,
            $this->buildResumeUrl($targetUrl, $suggestionId, $providerSlug, $resumeState),
            $resumeState
        ));
    }

    protected function buildResumeUrl(string $targetUrl, int $suggestionId, string $providerSlug, string $resumeState = 'reconnected'): string
    {
        $separator = str_contains($targetUrl, '?') ? '&' : '?';

        return $targetUrl . $separator . http_build_query([
            'automation_resume' => 1,
            'automation_suggestion_ids' => (string) $suggestionId,
            'automation_provider' => $providerSlug,
            'automation_resume_state' => $resumeState,
        ]);
    }
}
