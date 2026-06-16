<?php

namespace Vendor\Automation\Support;

use App\Models\User;

class AutomationTenantResolver
{
    public static function resolve(?User $user = null): int
    {
        $user ??= auth()->user();

        return max(0, (int) session('current_tenant_id', $user?->tenant_id ?? 0));
    }

    public static function userCanAccessTenant(?User $user, int $tenantId): bool
    {
        if (!$user || $tenantId <= 0) {
            return false;
        }

        if (method_exists($user, 'hasTenantAccess')) {
            return (bool) $user->hasTenantAccess($tenantId);
        }

        return (int) $user->tenant_id === $tenantId;
    }
}
