<?php

namespace Vendor\Automation\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;
use Vendor\Automation\Events\AutomationEventFailed;
use Vendor\Automation\Events\AutomationEventProcessed;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationLog;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Automation\Registries\ActionRegistry;
use Vendor\Automation\Support\AutomationReconnectResolver;
use Vendor\Automation\Support\AutomationTenantResolver;

class AutomationExecutor
{
    public function __construct(
        protected ActionRegistry $actionRegistry
    ) {
    }

    public function execute(AutomationEvent $automationEvent): AutomationEvent
    {
        if (in_array($automationEvent->status, [
            AutomationEvent::STATUS_COMPLETED,
            AutomationEvent::STATUS_SKIPPED,
        ], true)) {
            return $automationEvent;
        }

        $this->assertTenantScope((int) $automationEvent->tenant_id);

        $automationEvent->forceFill([
            'status' => AutomationEvent::STATUS_PROCESSING,
            'attempts' => (int) $automationEvent->attempts + 1,
        ])->save();

        $this->writeLog(
            $automationEvent,
            level: 'info',
            status: AutomationEvent::STATUS_PROCESSING,
            message: 'Exécution automation démarrée.',
        );

        $action = $this->actionRegistry->resolve((string) $automationEvent->action_type);
        if (!$action) {
            return $this->markFailed(
                $automationEvent,
                "Aucune action enregistrée pour le type [{$automationEvent->action_type}]."
            );
        }

        try {
            $response = $action->execute($automationEvent, $automationEvent->suggestion);

            $automationEvent->forceFill([
                'status' => AutomationEvent::STATUS_COMPLETED,
                'response' => $response,
                'processed_at' => now(),
                'failed_at' => null,
                'last_error' => null,
            ])->save();

            $this->writeLog(
                $automationEvent,
                level: 'info',
                status: AutomationEvent::STATUS_COMPLETED,
                message: 'Exécution automation terminée.',
                response: $response
            );

            event(new AutomationEventProcessed($automationEvent->fresh()));

            return $automationEvent->fresh();
        } catch (Throwable $e) {
            return $this->markFailed($automationEvent, $e->getMessage(), [
                'exception' => get_class($e),
            ]);
        }
    }

    protected function markFailed(AutomationEvent $automationEvent, string $message, array $context = []): AutomationEvent
    {
        $freshEvent = DB::transaction(function () use ($automationEvent, $message, $context) {
            $lockedEvent = AutomationEvent::query()
                ->whereKey($automationEvent->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $reopenedSuggestion = $this->reopenSuggestionForReconnectFailure($lockedEvent, $message);

            $lockedEvent->forceFill([
                'status' => AutomationEvent::STATUS_FAILED,
                'last_error' => $message,
                'failed_at' => now(),
                'idempotency_key' => $reopenedSuggestion ? null : $lockedEvent->idempotency_key,
            ])->save();

            $this->writeLog(
                $lockedEvent,
                level: 'error',
                status: AutomationEvent::STATUS_FAILED,
                message: $message,
                context: array_filter(array_merge($context, [
                    'suggestion_reopened' => (bool) $reopenedSuggestion,
                    'reopened_suggestion_id' => $reopenedSuggestion ? (int) $reopenedSuggestion->id : null,
                ]), static fn ($value) => $value !== null)
            );

            return $lockedEvent->fresh();
        });

        event(new AutomationEventFailed($freshEvent, $message));

        return $freshEvent;
    }

    protected function reopenSuggestionForReconnectFailure(AutomationEvent $automationEvent, string $message): ?AutomationSuggestion
    {
        if (!AutomationReconnectResolver::messageRequiresReconnect($message) || !$automationEvent->triggered_by_suggestion_id) {
            return null;
        }

        $suggestion = AutomationSuggestion::query()
            ->whereKey((int) $automationEvent->triggered_by_suggestion_id)
            ->lockForUpdate()
            ->first();

        if (!$suggestion) {
            return null;
        }

        $pendingDedupeKey = $suggestion->dedupe_key
            ? ((int) $suggestion->tenant_id) . ':' . (string) $suggestion->dedupe_key
            : null;

        if ($pendingDedupeKey) {
            $keyAlreadyUsed = AutomationSuggestion::query()
                ->where('pending_dedupe_key', $pendingDedupeKey)
                ->whereKeyNot($suggestion->getKey())
                ->exists();

            if ($keyAlreadyUsed) {
                $pendingDedupeKey = null;
            }
        }

        $suggestion->forceFill([
            'status' => AutomationSuggestion::STATUS_PENDING,
            'accepted_at' => null,
            'accepted_by' => null,
            'pending_dedupe_key' => $pendingDedupeKey,
            'expires_at' => $suggestion->expires_at && $suggestion->expires_at->isPast()
                ? now()->addHours((int) config('automation.suggestions.default_expiration_hours', 72))
                : $suggestion->expires_at,
        ])->save();

        return $suggestion->fresh();
    }
    protected function writeLog(
        AutomationEvent $automationEvent,
        string $level,
        string $status,
        string $message,
        array $response = [],
        array $context = []
    ): void {
        $modelClass = config('automation.models.log', AutomationLog::class);

        $modelClass::query()->create([
            'tenant_id' => (int) $automationEvent->tenant_id,
            'user_id' => $automationEvent->user_id ? (int) $automationEvent->user_id : null,
            'automation_event_id' => (int) $automationEvent->id,
            'automation_suggestion_id' => $automationEvent->triggered_by_suggestion_id
                ? (int) $automationEvent->triggered_by_suggestion_id
                : null,
            'event_name' => (string) $automationEvent->event_name,
            'action_type' => (string) $automationEvent->action_type,
            'level' => $level,
            'status' => $status,
            'message' => $message,
            'response' => $response ?: null,
            'context' => $context ?: null,
        ]);
    }

    protected function assertTenantScope(int $tenantId): void
    {
        if (auth()->check() && !AutomationTenantResolver::userCanAccessTenant(auth()->user(), $tenantId)) {
            throw new RuntimeException('Accès interdit à cette automation pour un autre tenant.');
        }
    }
}
