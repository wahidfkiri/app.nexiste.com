<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureTenantPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $permissions = $this->normalizePermissions($permissions);
        if ($permissions === []) {
            return $next($request);
        }

        $tenantId = (int) (session('current_tenant_id') ?: ($user->tenant_id ?? 0));

        if ($this->isPrivilegedTenantUser($user, $tenantId)) {
            return $next($request);
        }

        if (!$this->rbacTablesAreReady() || !method_exists($user, 'hasTenantPermission')) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            try {
                if ($user->hasTenantPermission($permission, $tenantId)) {
                    return $next($request);
                }
            } catch (Throwable) {
                // If RBAC is temporarily unavailable during install/update, keep the app usable.
                return $next($request);
            }
        }

        return $this->deny($request, $permissions);
    }

    private function deny(Request $request, array $permissions): Response
    {
        $message = 'Permission insuffisante pour accéder à cette ressource.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'permissions' => $permissions,
            ], 403);
        }

        if (Route::has('dashboard')) {
            return redirect()->route('dashboard')->with('error', $message);
        }

        abort(403, $message);
    }

    private function normalizePermissions(array $permissions): array
    {
        return collect($permissions)
            ->flatMap(fn (string $permission): array => preg_split('/[|,]/', $permission) ?: [])
            ->map(fn (string $permission): string => trim($permission))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function isPrivilegedTenantUser($user, int $tenantId): bool
    {
        if (method_exists($user, 'hasTenantRole') && $user->hasTenantRole(['owner', 'admin'], $tenantId)) {
            return true;
        }

        return (bool) ($user->is_tenant_owner ?? false)
            || in_array((string) ($user->role_in_tenant ?? ''), ['owner', 'admin'], true);
    }

    private function rbacTablesAreReady(): bool
    {
        return Schema::hasTable('permissions')
            && Schema::hasTable('roles')
            && Schema::hasTable('role_has_permissions');
    }
}
