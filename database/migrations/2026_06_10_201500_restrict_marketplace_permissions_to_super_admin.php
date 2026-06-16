<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

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

        $permissionNames = config('rbac.platform_only_permissions', [
            'marketplace.read',
            'extensions.read',
            'extensions.manage',
            'extensions.settings',
        ]);

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissionNames)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($permissionIds === []) {
            return;
        }

        $tenantRoleIds = DB::table('roles')
            ->whereNotNull('tenant_id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($tenantRoleIds === []) {
            return;
        }

        DB::table('role_has_permissions')
            ->whereIn('role_id', $tenantRoleIds)
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
