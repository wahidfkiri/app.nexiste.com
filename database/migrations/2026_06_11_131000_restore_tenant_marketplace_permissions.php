<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Vendor\Rbac\Services\TenantRoleService;

return new class extends Migration
{
    private const MARKETPLACE_PERMISSIONS = [
        'marketplace.read',
        'extensions.read',
        'extensions.manage',
        'extensions.settings',
    ];

    public function up(): void
    {
        if (
            !Schema::hasTable('roles')
            || !Schema::hasTable('permissions')
            || !Schema::hasTable('role_has_permissions')
        ) {
            return;
        }

        if (class_exists(TenantRoleService::class)) {
            app(TenantRoleService::class)->ensureGlobalPermissions();

            if (Schema::hasTable('tenants')) {
                foreach (DB::table('tenants')->pluck('id') as $tenantId) {
                    app(TenantRoleService::class)->ensureTenantRoles((int) $tenantId);
                }
            }
        }

        $permissionQuery = DB::table('permissions')->whereIn('name', self::MARKETPLACE_PERMISSIONS);
        if (Schema::hasColumn('permissions', 'tenant_id')) {
            $permissionQuery->whereNull('tenant_id');
        }

        $permissionIds = $permissionQuery
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($permissionIds === []) {
            return;
        }

        $roleQuery = DB::table('roles')
            ->whereIn('name', ['owner', 'admin'])
            ->where('guard_name', 'web');

        if (Schema::hasColumn('roles', 'tenant_id')) {
            $roleQuery->whereNotNull('tenant_id');
        }

        $roleIds = $roleQuery
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }

        $this->clearPermissionCache();
    }

    public function down(): void
    {
        if (
            !Schema::hasTable('roles')
            || !Schema::hasTable('permissions')
            || !Schema::hasTable('role_has_permissions')
        ) {
            return;
        }

        $permissionQuery = DB::table('permissions')->whereIn('name', self::MARKETPLACE_PERMISSIONS);
        if (Schema::hasColumn('permissions', 'tenant_id')) {
            $permissionQuery->whereNull('tenant_id');
        }

        $permissionIds = $permissionQuery
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $roleQuery = DB::table('roles')
            ->whereIn('name', ['owner', 'admin'])
            ->where('guard_name', 'web');

        if (Schema::hasColumn('roles', 'tenant_id')) {
            $roleQuery->whereNotNull('tenant_id');
        }

        $roleIds = $roleQuery
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($permissionIds !== [] && $roleIds !== []) {
            DB::table('role_has_permissions')
                ->whereIn('role_id', $roleIds)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        $this->clearPermissionCache();
    }

    private function clearPermissionCache(): void
    {
        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
