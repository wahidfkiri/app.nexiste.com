<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Vendor\Rbac\Services\TenantRoleService;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('roles')
            || !Schema::hasTable('permissions')
            || !Schema::hasTable('role_has_permissions')
        ) {
            return;
        }

        if (Schema::hasTable('tenants')) {
            foreach (DB::table('tenants')->pluck('id') as $tenantId) {
                app(TenantRoleService::class)->ensureTenantRoles((int) $tenantId);
            }
        }

        $targetPermissionNames = [
            'dashboard.read',
            'home.read',
            'clients.read',
        ];

        $targetPermissionIds = DB::table('permissions')
            ->whereNull('tenant_id')
            ->whereIn('name', $targetPermissionNames)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($targetPermissionIds === []) {
            return;
        }

        $legacyMarkers = [
            'marketplace.read',
            'extensions.read',
            'google-drive.view',
            'stock.read',
            'invoices.read',
        ];

        $viewerRoles = DB::table('roles')
            ->where('name', 'viewer')
            ->where('guard_name', 'web')
            ->get(['id']);

        foreach ($viewerRoles as $role) {
            $currentPermissionNames = DB::table('role_has_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('role_has_permissions.role_id', (int) $role->id)
                ->pluck('permissions.name')
                ->all();

            $looksLikeLegacyWideViewer = count($currentPermissionNames) >= 10
                && count(array_intersect($legacyMarkers, $currentPermissionNames)) >= 3;

            if (!$looksLikeLegacyWideViewer) {
                continue;
            }

            DB::table('role_has_permissions')
                ->where('role_id', (int) $role->id)
                ->delete();

            foreach ($targetPermissionIds as $permissionId) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => (int) $role->id,
                ]);
            }
        }

        $this->clearPermissionCache();
    }

    public function down(): void
    {
        $this->clearPermissionCache();
    }

    private function clearPermissionCache(): void
    {
        if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
