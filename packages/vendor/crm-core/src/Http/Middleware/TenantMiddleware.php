<?php

namespace Vendor\CrmCore\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Vendor\CrmCore\Models\Tenant;
use Vendor\Rbac\Services\TenantRoleService;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return $next($request);
        }

        /** @var User $user */
        $user = auth()->user();
        $baseTenantId = (int) ($user->getOriginal('tenant_id') ?: $user->tenant_id ?: 0);
        $requestedTenantId = (int) session('current_tenant_id', $baseTenantId);

        $tenantId = $this->resolveAllowedTenantId($user, $requestedTenantId, $baseTenantId);
        if ($tenantId <= 0) {
            abort(403, 'Aucun espace actif disponible pour ce compte.');
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant || $tenant->status !== 'active') {
            abort(403, 'Espace indisponible ou inactif.');
        }

        $this->applyUserContextForTenant($user, $tenantId);
        app(TenantRoleService::class)->ensureTenantRoles($tenantId);

        view()->share('currentTenant', $tenant);
        $request->merge(['current_tenant' => $tenant]);
        session()->put('current_tenant_id', $tenant->id);

        return $next($request);
    }

    private function resolveAllowedTenantId(User $user, int $requestedTenantId, int $baseTenantId): int
    {
        if ($requestedTenantId > 0 && $user->hasTenantAccess($requestedTenantId)) {
            return $requestedTenantId;
        }

        if ($baseTenantId > 0 && $user->hasTenantAccess($baseTenantId)) {
            return $baseTenantId;
        }

        if (Schema::hasTable('tenant_user_memberships')) {
            $firstMembership = $user->tenantMemberships()
                ->where('status', 'active')
                ->orderByDesc('is_tenant_owner')
                ->orderBy('id')
                ->first();

            if ($firstMembership) {
                return (int) $firstMembership->tenant_id;
            }
        }

        return 0;
    }

    private function applyUserContextForTenant(User $user, int $tenantId): void
    {
        $membership = $user->membershipForTenant($tenantId);

        if ($membership) {
            $user->setAttribute('tenant_id', $tenantId);
            $user->setAttribute('role_in_tenant', (string) $membership->role_in_tenant);
            $user->setAttribute('is_tenant_owner', (bool) $membership->is_tenant_owner);
            return;
        }

        if ((int) $user->getOriginal('tenant_id') === $tenantId) {
            $user->setAttribute('tenant_id', $tenantId);
        }
    }
}
