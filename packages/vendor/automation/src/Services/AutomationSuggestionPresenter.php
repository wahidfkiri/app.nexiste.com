<?php

namespace Vendor\Automation\Services;

use Illuminate\Support\Collection;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Automation\Support\AutomationTenantResolver;

class AutomationSuggestionPresenter
{
    public function __construct(
        protected AutomationPreferenceService $preferences
    ) {
    }

    public function fetch(array $filters = [], ?int $tenantId = null): Collection
    {
        $tenantId = $tenantId ?? $this->resolveTenantId();
        $modelClass = config('automation.models.suggestion', AutomationSuggestion::class);

        $query = $modelClass::query()->where('tenant_id', $tenantId);

        $ids = collect($filters['ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        if ($ids->isNotEmpty()) {
            $query->whereIn('id', $ids->all());
        }

        if (!empty($filters['source_event'])) {
            $query->where('source_event', (string) $filters['source_event']);
        }

        if (!empty($filters['source_type'])) {
            $query->where('source_type', (string) $filters['source_type']);
        }

        if (array_key_exists('source_id', $filters) && $filters['source_id'] !== null && $filters['source_id'] !== '') {
            $query->where('source_id', (string) $filters['source_id']);
        }

        $status = (string) ($filters['status'] ?? AutomationSuggestion::STATUS_PENDING);
        if ($status !== 'all') {
            $query->where('status', $status);
            if ($status === AutomationSuggestion::STATUS_PENDING) {
                $query->where(function ($builder) {
                    $builder->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });
            }
        }

        $limit = (int) ($filters['limit'] ?? 12);
        $limit = max(1, min($limit, 50));

        return $query
            ->orderByDesc('confidence')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    public function buildPromptForSource(
        string $sourceEvent,
        ?string $sourceType,
        int|string|null $sourceId,
        ?int $tenantId = null,
        array $extra = []
    ): array {
        $enabled = $this->preferences->suggestionsEnabled($tenantId);

        if (!$enabled) {
            return array_merge([
                'enabled' => false,
                'should_prompt' => false,
                'source_event' => $sourceEvent,
                'source_type' => $sourceType,
                'source_id' => $sourceId !== null ? (string) $sourceId : null,
                'title' => $this->titleForSourceEvent($sourceEvent),
                'subtitle' => __('automation::automation.presenter.suggestions_disabled'),
                'count' => 0,
                'pending_count' => 0,
                'settings_url' => $this->preferences->settingsUrl(),
                'suggestions' => [],
            ], $extra);
        }

        $suggestions = $this->fetch([
            'source_event' => $sourceEvent,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'status' => AutomationSuggestion::STATUS_PENDING,
            'limit' => 12,
        ], $tenantId);

        $items = $this->presentCollection($suggestions);
        $count = count($items);

        return array_merge([
            'enabled' => true,
            'should_prompt' => $count > 0,
            'source_event' => $sourceEvent,
            'source_type' => $sourceType,
            'source_id' => $sourceId !== null ? (string) $sourceId : null,
            'title' => $this->titleForSourceEvent($sourceEvent),
            'subtitle' => $count > 0
                ? __('automation::automation.presenter.suggestions_available', ['count' => $count])
                : __('automation::automation.presenter.suggestions_empty'),
            'count' => $count,
            'pending_count' => $count,
            'settings_url' => $this->preferences->settingsUrl(),
            'suggestions' => $items,
        ], $extra);
    }

    public function presentCollection(Collection $suggestions): array
    {
        $extensions = app(\Vendor\Automation\Services\ExtensionAvailabilityService::class);

        return $suggestions
            ->filter(function (AutomationSuggestion $suggestion) use ($extensions) {
                $meta = (array) ($suggestion->meta ?? []);
                $payload = (array) ($suggestion->payload ?? []);
                
                $integration = (string) ($meta['integration'] ?? ($payload['extension_slug'] ?? ''));
                
                if ($integration && !$extensions->isAvailableGlobally($integration)) {
                    return false;
                }

                $installed = !array_key_exists('installed', $meta) || (bool) $meta['installed'];
                
                return $installed || $suggestion->type === 'install_extension';
            })
            ->map(fn (AutomationSuggestion $suggestion) => $this->present($suggestion))
            ->values()
            ->all();
    }

    public function present(AutomationSuggestion $suggestion): array
    {
        $meta = (array) ($suggestion->meta ?? []);
        $payload = (array) ($suggestion->payload ?? []);
        $installed = !array_key_exists('installed', $meta) || (bool) $meta['installed'];
        $isInstall = $suggestion->type === 'install_extension' || !$installed;
        $theme = $this->themeFor($suggestion);
        $confidencePercent = (int) round(((float) $suggestion->confidence) * 100);
        $primaryLabel = (string) ($meta['primary_label'] ?? ($isInstall
            ? __('automation::automation.presenter.primary_label_install')
            : __('automation::automation.presenter.primary_label_accept')));
        $secondaryLabel = (string) ($meta['secondary_label'] ?? __('automation::automation.presenter.secondary_label_ignore'));
        $integrationSlug = (string) ($meta['integration'] ?? ($payload['extension_slug'] ?? 'automation'));

        return [
            'id' => (int) $suggestion->id,
            'type' => (string) $suggestion->type,
            'label' => (string) $suggestion->label,
            'status' => (string) $suggestion->status,
            'is_actionable' => $suggestion->isActionable(),
            'confidence' => (float) $suggestion->confidence,
            'confidence_percent' => $confidencePercent,
            'confidence_label' => $this->confidenceLabel((float) $suggestion->confidence),
            'source_event' => (string) $suggestion->source_event,
            'source_type' => $suggestion->source_type,
            'source_id' => $suggestion->source_id,
            'payload' => $payload,
            'meta' => $meta,
            'integration' => [
                'slug' => $integrationSlug,
                'label' => $this->integrationLabel($integrationSlug),
                'installed' => $installed,
                'target_url' => $meta['target_url'] ?? null,
                'target_blank' => $this->shouldOpenInNewTab($integrationSlug, $meta, $payload),
            ],
            'theme' => $theme,
            'intent' => $isInstall ? 'install' : 'automation',
            'primary_label' => $primaryLabel,
            'secondary_label' => $secondaryLabel,
            'expires_at' => optional($suggestion->expires_at)?->toIso8601String(),
            'expires_human' => $suggestion->expires_at ? $suggestion->expires_at->diffForHumans() : null,
            'created_at' => optional($suggestion->created_at)?->toIso8601String(),
            'status_label' => $this->statusLabel((string) $suggestion->status),
        ];
    }

    protected function titleForSourceEvent(string $sourceEvent): string
    {
        $key = 'automation::automation.presenter.source_titles.' . $sourceEvent;

        return __($key) !== $key
            ? __($key)
            : __('automation::automation.presenter.source_titles.default');
    }

    protected function confidenceLabel(float $confidence): string
    {
        return match (true) {
            $confidence >= 0.9 => __('automation::automation.presenter.confidence.very_relevant'),
            $confidence >= 0.8 => __('automation::automation.presenter.confidence.relevant'),
            $confidence >= 0.65 => __('automation::automation.presenter.confidence.useful'),
            default => __('automation::automation.presenter.confidence.optional'),
        };
    }

    protected function statusLabel(string $status): string
    {
        return match ($status) {
            AutomationSuggestion::STATUS_ACCEPTED => __('automation::automation.presenter.statuses.accepted'),
            AutomationSuggestion::STATUS_REJECTED => __('automation::automation.presenter.statuses.rejected'),
            AutomationSuggestion::STATUS_EXPIRED => __('automation::automation.presenter.statuses.expired'),
            default => __('automation::automation.presenter.statuses.pending'),
        };
    }

    protected function integrationLabel(string $slug): string
    {
        $key = 'automation::automation.presenter.integrations.' . $slug;

        return __($key) !== $key
            ? __($key)
            : ucfirst(str_replace('-', ' ', $slug ?: 'automation'));
    }

    protected function themeFor(AutomationSuggestion $suggestion): array
    {
        $meta = (array) ($suggestion->meta ?? []);
        $payload = (array) ($suggestion->payload ?? []);
        $integration = (string) ($meta['integration'] ?? ($payload['extension_slug'] ?? 'automation'));
        $type = (string) $suggestion->type;

        return match (true) {
            $type === 'install_extension' => [
                'icon' => 'fas fa-store',
                'color' => '#1d4ed8',
                'background' => 'rgba(37,99,235,.12)',
            ],
            $integration === 'google-gmail' => [
                'icon' => 'fas fa-envelope',
                'color' => '#ea4335',
                'background' => 'rgba(234,67,53,.12)',
            ],
            $integration === 'google-calendar' => [
                'icon' => 'fas fa-calendar-days',
                'color' => '#0f9d58',
                'background' => 'rgba(15,157,88,.12)',
            ],
            $integration === 'google-drive' => [
                'icon' => 'fas fa-folder-open',
                'color' => '#1a73e8',
                'background' => 'rgba(26,115,232,.12)',
            ],
            $integration === 'dropbox' => [
                'icon' => 'fab fa-dropbox',
                'color' => '#0061ff',
                'background' => 'rgba(0,97,255,.12)',
            ],
            $integration === 'google-meet' => [
                'icon' => 'fas fa-video',
                'color' => '#34a853',
                'background' => 'rgba(52,168,83,.12)',
            ],
            $integration === 'google-sheets' => [
                'icon' => 'fas fa-table-cells',
                'color' => '#188038',
                'background' => 'rgba(24,128,56,.12)',
            ],
            $integration === 'google-docx' => [
                'icon' => 'fas fa-file-word',
                'color' => '#1a73e8',
                'background' => 'rgba(26,115,232,.12)',
            ],
            $integration === 'notion-workspace' => [
                'icon' => 'fas fa-book-open',
                'color' => '#111827',
                'background' => 'rgba(17,24,39,.10)',
            ],
            $integration === 'invoice' => [
                'icon' => 'fas fa-file-invoice-dollar',
                'color' => '#2563eb',
                'background' => 'rgba(37,99,235,.12)',
            ],
            $integration === 'projects' => [
                'icon' => 'fas fa-diagram-project',
                'color' => '#7c3aed',
                'background' => 'rgba(124,58,237,.12)',
            ],
            $integration === 'chatbot' => [
                'icon' => 'fas fa-comments',
                'color' => '#0891b2',
                'background' => 'rgba(8,145,178,.12)',
            ],
            $integration === 'slack' => [
                'icon' => 'fab fa-slack',
                'color' => '#611f69',
                'background' => 'rgba(97,31,105,.12)',
            ],
            $integration === 'users' => [
                'icon' => 'fas fa-user-plus',
                'color' => '#0284c7',
                'background' => 'rgba(2,132,199,.12)',
            ],
            $integration === 'marketplace' => [
                'icon' => 'fas fa-store',
                'color' => '#1d4ed8',
                'background' => 'rgba(29,78,216,.12)',
            ],
            default => [
                'icon' => 'fas fa-wand-magic-sparkles',
                'color' => '#0f172a',
                'background' => 'rgba(15,23,42,.08)',
            ],
        };
    }

    protected function resolveTenantId(): int
    {
        return AutomationTenantResolver::resolve();
    }

    protected function shouldOpenInNewTab(string $integrationSlug, array $meta, array $payload): bool
    {
        if (array_key_exists('target_blank', $meta)) {
            return (bool) $meta['target_blank'];
        }

        if (array_key_exists('target_blank', $payload)) {
            return (bool) $payload['target_blank'];
        }

        return in_array($integrationSlug, ['notion-workspace', 'google-calendar'], true);
    }
}
