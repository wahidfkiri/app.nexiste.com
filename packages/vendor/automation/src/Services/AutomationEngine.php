<?php

namespace Vendor\Automation\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Vendor\Automation\Data\SuggestionDefinition;
use Vendor\Automation\Events\AutomationEventQueued;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Automation\Registries\ActionRegistry;
use Vendor\Automation\Registries\SuggestionRegistry;
use Vendor\Automation\Support\AutomationTenantResolver;

class AutomationEngine
{
    public function __construct(
        protected SuggestionRegistry $suggestionRegistry,
        protected ActionRegistry $actionRegistry,
        protected AutomationPreferenceService $preferences
    ) {
    }

    public function capture(
        string $sourceEvent,
        array $context = [],
        ?int $tenantId = null,
        ?int $userId = null,
        ?string $sourceType = null,
        int|string|null $sourceId = null,
    ): Collection {
        $tenantId = $this->resolveTenantId($tenantId);
        $this->assertTenantScope($tenantId);

        if (!$this->preferences->suggestionsEnabled($tenantId)) {
            return collect();
        }

        $userId = $userId ?? (auth()->check() ? (int) auth()->id() : null);
        $sourceType = $sourceType ?? (isset($context['source']) && $context['source'] instanceof Model
            ? $context['source']::class
            : (string) ($context['source_type'] ?? ''));

        if ($sourceId === null && isset($context['source'])) {
            $source = $context['source'];
            if ($source instanceof Model) {
                $sourceId = $source->getKey();
            } else {
                $sourceId = $context['source_id'] ?? null;
            }
        }

        return collect($this->suggestionRegistry->providersFor($sourceEvent))
            ->flatMap(function ($provider) use ($sourceEvent, $context) {
                $items = [];
                foreach ($provider->suggest($sourceEvent, $context) as $suggestion) {
                    $items[] = $this->normalizeDefinition($suggestion);
                }

                return $items;
            })
            ->map(function (SuggestionDefinition $definition) use ($tenantId, $userId, $sourceEvent, $sourceType, $sourceId) {
                return $this->storeSuggestion(
                    $tenantId,
                    $userId,
                    $sourceEvent,
                    $sourceType,
                    $sourceId,
                    $definition
                );
            })
            ->values();
    }

    public function accept(AutomationSuggestion|int $suggestion, ?int $userId = null, array $payloadOverrides = []): AutomationEvent
    {
        $suggestionId = $suggestion instanceof AutomationSuggestion ? (int) $suggestion->getKey() : (int) $suggestion;
        $userId = $userId ?? (auth()->check() ? (int) auth()->id() : null);

        return DB::transaction(function () use ($suggestionId, $userId, $payloadOverrides) {
            $lockedSuggestion = $this->newSuggestionQuery()
                ->whereKey($suggestionId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTenantScope((int) $lockedSuggestion->tenant_id);
            $lockedSuggestion->markExpiredIfNeeded();

            $idempotencyKey = $this->buildEventIdempotencyKey($lockedSuggestion);
            $existingEvent = $this->newEventQuery()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingEvent) {
                return $existingEvent;
            }

            if (!$lockedSuggestion->isActionable()) {
                throw new RuntimeException('Cette suggestion ne peut plus être exécutée.');
            }

            $mergedPayload = array_replace_recursive($lockedSuggestion->payload ?? [], $payloadOverrides);

            $lockedSuggestion->forceFill([
                'status' => AutomationSuggestion::STATUS_ACCEPTED,
                'accepted_at' => now(),
                'accepted_by' => $userId,
                'pending_dedupe_key' => null,
                'payload' => $mergedPayload,
            ])->save();

            $automationEvent = $this->newEventQuery()->create([
                'tenant_id' => (int) $lockedSuggestion->tenant_id,
                'user_id' => $userId,
                'event_name' => $this->buildAutomationEventName((string) $lockedSuggestion->type),
                'action_type' => (string) $lockedSuggestion->type,
                'payload' => $mergedPayload,
                'status' => AutomationEvent::STATUS_QUEUED,
                'idempotency_key' => $idempotencyKey,
                'triggered_by_suggestion_id' => (int) $lockedSuggestion->id,
                'dispatched_at' => now(),
            ]);

            DB::afterCommit(function () use ($automationEvent) {
                event(new AutomationEventQueued($automationEvent->fresh()));
            });

            return $automationEvent;
        });
    }

    public function reject(AutomationSuggestion|int $suggestion, ?int $userId = null, ?string $reason = null): AutomationSuggestion
    {
        $suggestionId = $suggestion instanceof AutomationSuggestion ? (int) $suggestion->getKey() : (int) $suggestion;
        $userId = $userId ?? (auth()->check() ? (int) auth()->id() : null);

        return DB::transaction(function () use ($suggestionId, $userId, $reason) {
            $lockedSuggestion = $this->newSuggestionQuery()
                ->whereKey($suggestionId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTenantScope((int) $lockedSuggestion->tenant_id);
            $lockedSuggestion->markExpiredIfNeeded();

            if (!$lockedSuggestion->isActionable()) {
                return $lockedSuggestion;
            }

            $lockedSuggestion->forceFill([
                'status' => AutomationSuggestion::STATUS_REJECTED,
                'rejected_at' => now(),
                'rejected_by' => $userId,
                'rejection_reason' => $reason,
                'pending_dedupe_key' => null,
            ])->save();

            return $lockedSuggestion;
        });
    }

    protected function normalizeDefinition(SuggestionDefinition|array $suggestion): SuggestionDefinition
    {
        if ($suggestion instanceof SuggestionDefinition) {
            return $suggestion;
        }

        if (!is_array($suggestion) || empty($suggestion['type']) || empty($suggestion['label'])) {
            throw new RuntimeException('Chaque suggestion automation doit définir au minimum un type et un label.');
        }

        return new SuggestionDefinition(
            type: (string) $suggestion['type'],
            label: (string) $suggestion['label'],
            confidence: (float) ($suggestion['confidence'] ?? 0.5),
            payload: (array) ($suggestion['payload'] ?? []),
            meta: (array) ($suggestion['meta'] ?? []),
            dedupeKey: isset($suggestion['dedupe_key']) ? (string) $suggestion['dedupe_key'] : null,
            expiresAt: $this->normalizeExpiration($suggestion['expires_at'] ?? null),
        );
    }

    protected function storeSuggestion(
        int $tenantId,
        ?int $userId,
        string $sourceEvent,
        ?string $sourceType,
        int|string|null $sourceId,
        SuggestionDefinition $definition
    ): AutomationSuggestion {
        $dedupeKey = $definition->dedupeKey ?: $this->buildSuggestionDedupeKey(
            $tenantId,
            $sourceEvent,
            $sourceType,
            $sourceId,
            $definition
        );
        $pendingDedupeKey = $tenantId . ':' . $dedupeKey;

        $attributes = [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'source_event' => $sourceEvent,
            'source_type' => $sourceType ?: null,
            'source_id' => $sourceId !== null ? (string) $sourceId : null,
            'type' => $definition->type,
            'label' => $definition->label,
            'confidence' => round($definition->confidence, 2),
            'payload' => $definition->payload,
            'meta' => $definition->meta,
            'status' => AutomationSuggestion::STATUS_PENDING,
            'dedupe_key' => $dedupeKey,
            'pending_dedupe_key' => $pendingDedupeKey,
            'expires_at' => $definition->expiresAt ?: now()->addHours((int) config('automation.suggestions.default_expiration_hours', 72)),
        ];

        return $this->newSuggestionQuery()->firstOrCreate(
            ['pending_dedupe_key' => $pendingDedupeKey],
            $attributes
        );
    }

    protected function buildAutomationEventName(string $type): string
    {
        $prefix = (string) config('automation.event_prefix', 'automation.execute');

        return $prefix . '.' . $type;
    }

    protected function buildSuggestionDedupeKey(
        int $tenantId,
        string $sourceEvent,
        ?string $sourceType,
        int|string|null $sourceId,
        SuggestionDefinition $definition
    ): string {
        return sha1(json_encode([
            'tenant_id' => $tenantId,
            'source_event' => $sourceEvent,
            'source_type' => $sourceType,
            'source_id' => $sourceId !== null ? (string) $sourceId : null,
            'type' => $definition->type,
            'payload' => $definition->payload,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: uniqid('automation_', true));
    }

    protected function buildEventIdempotencyKey(AutomationSuggestion $suggestion): string
    {
        return sha1(sprintf(
            '%s|%s|%s',
            (int) $suggestion->tenant_id,
            (int) $suggestion->id,
            (string) $suggestion->type
        ));
    }

    protected function resolveTenantId(?int $tenantId = null): int
    {
        $tenantId = $tenantId ?? AutomationTenantResolver::resolve();
        if ($tenantId <= 0) {
            throw new RuntimeException('Impossible de résoudre le tenant pour cette automation.');
        }

        return $tenantId;
    }

    protected function assertTenantScope(int $tenantId): void
    {
        if (auth()->check() && !AutomationTenantResolver::userCanAccessTenant(auth()->user(), $tenantId)) {
            throw new RuntimeException('Accès interdit à cette automation pour un autre tenant.');
        }
    }

    protected function newSuggestionQuery()
    {
        $modelClass = config('automation.models.suggestion', AutomationSuggestion::class);

        return $modelClass::query();
    }

    protected function newEventQuery()
    {
        $modelClass = config('automation.models.event', AutomationEvent::class);

        return $modelClass::query();
    }

    protected function normalizeExpiration(mixed $expiresAt): ?CarbonInterface
    {
        if ($expiresAt instanceof CarbonInterface) {
            return $expiresAt;
        }

        if ($expiresAt === null || $expiresAt === '') {
            return null;
        }

        return Carbon::parse($expiresAt);
    }
}
