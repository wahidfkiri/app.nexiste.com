<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Vendor\CrmCore\Models\Tenant;
use Vendor\Rbac\Services\TenantRoleService;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (config('rbac.permission_groups', []) as $groupKey => $groupDefinition) {
            foreach (($groupDefinition['permissions'] ?? []) as $name => $label) {
                Permission::query()->firstOrCreate(
                    [
                        'name' => $name,
                        'guard_name' => 'web',
                    ],
                    [
                        'tenant_id' => null,
                        'label' => $label,
                        'group' => $groupKey,
                    ]
                );
            }
        }

        foreach (['super_admin', 'super-admin'] as $roleName) {
            Role::query()->firstOrCreate(
                [
                    'tenant_id' => null,
                    'name' => $roleName,
                    'guard_name' => 'web',
                ],
                [
                    'label' => 'Super administrateur',
                    'description' => 'Accès global plateforme',
                    'color' => '#dc2626',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }

        $tenantRoleService = app(TenantRoleService::class);
        foreach (Tenant::query()->get(['id']) as $tenant) {
            $tenantRoleService->ensureTenantRoles((int) $tenant->id);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
