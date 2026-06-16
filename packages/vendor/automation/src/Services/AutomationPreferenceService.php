<?php

namespace Vendor\Automation\Services;

use Vendor\Automation\Support\AutomationTenantResolver;
use Vendor\CrmCore\Models\TenantSetting;

class AutomationPreferenceService
{
    public const SUGGESTIONS_ENABLED_KEY = 'automation_suggestions_enabled';

    public function suggestionsEnabled(?int $tenantId = null): bool
    {
        $tenantId = $tenantId ?? AutomationTenantResolver::resolve();
        $default = (bool) config('automation.suggestions.enabled_by_default', true);

        if ($tenantId <= 0) {
            return $default;
        }

        $value = TenantSetting::query()
            ->where('tenant_id', $tenantId)
            ->where('key', self::SUGGESTIONS_ENABLED_KEY)
            ->value('value');

        if ($value === null || $value === '') {
            return $default;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $normalized ?? in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    public function settingsUrl(): ?string
    {
        if (!app('router')->has('settings.global')) {
            return null;
        }

        return route('settings.global') . '#automation-suggestions-settings';
    }
}
