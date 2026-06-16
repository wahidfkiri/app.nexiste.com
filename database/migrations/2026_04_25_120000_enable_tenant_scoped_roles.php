<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_user_memberships') && !Schema::hasColumn('tenant_user_memberships', 'role_id')) {
            Schema::table('tenant_user_memberships', function (Blueprint $table): void {
                $table->unsignedBigInteger('role_id')->nullable()->after('tenant_id');
                $table->index(['tenant_id', 'role_id'], 'tum_tenant_role_idx');
            });
        }

        $this->makeRolesUniquePerTenant();
        $this->ensurePermissionCatalog();
        $this->ensureTenantDefaultRoles();
        $this->backfillMembershipRoleIds();
        $this->backfillInvitationRoleIds();
        $this->syncModelHasRoles();
    }

    public function down(): void
    {
        if (Schema::hasTable('tenant_user_memberships') && Schema::hasColumn('tenant_user_memberships', 'role_id')) {
            Schema::table('tenant_user_memberships', function (Blueprint $table): void {
                $table->dropIndex('tum_tenant_role_idx');
                $table->dropColumn('role_id');
            });
        }

        try {
            DB::statement('ALTER TABLE roles DROP INDEX roles_tenant_name_guard_name_unique');
        } catch (Throwable) {
            // ignore
        }

        try {
            DB::statement('ALTER TABLE roles ADD UNIQUE roles_name_guard_name_unique (name, guard_name)');
        } catch (Throwable) {
            // ignore
        }
    }

    private function makeRolesUniquePerTenant(): void
    {
        try {
            DB::statement('ALTER TABLE roles DROP INDEX roles_name_guard_name_unique');
        } catch (Throwable) {
            // ignore
        }

        try {
            DB::statement('ALTER TABLE roles ADD UNIQUE roles_tenant_name_guard_name_unique (tenant_id, name, guard_name)');
        } catch (Throwable) {
            // ignore
        }
    }

    private function ensurePermissionCatalog(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $groups = config('rbac.permission_groups', []);

        foreach ($groups as $groupKey => $groupDefinition) {
            foreach (($groupDefinition['permissions'] ?? []) as $name => $label) {
                DB::table('permissions')->updateOrInsert(
                    [
                        'name' => $name,
                        'guard_name' => 'web',
                    ],
                    [
                        'tenant_id' => null,
                        'label' => $label,
                        'group' => $groupKey,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    private function ensureTenantDefaultRoles(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('tenants')) {
            return;
        }

        $defaultRoles = config('rbac.default_roles', []);
        $defaultRolePermissions = config('rbac.default_role_permissions', []);
        $permissionsByName = DB::table('permissions')
            ->select(['id', 'name'])
            ->whereNull('tenant_id')
            ->get()
            ->keyBy('name');

        $tenantIds = DB::table('tenants')->pluck('id');

        foreach ($tenantIds as $tenantId) {
            foreach ($defaultRoles as $roleName => $definition) {
                $existingId = DB::table('roles')
                    ->where('tenant_id', (int) $tenantId)
                    ->where('name', $roleName)
                    ->where('guard_name', 'web')
                    ->value('id');

                if (!$existingId) {
                    $existingId = DB::table('roles')->insertGetId([
                        'tenant_id' => (int) $tenantId,
                        'name' => $roleName,
                        'label' => $definition['label'] ?? ucfirst($roleName),
                        'description' => $definition['description'] ?? null,
                        'color' => $definition['color'] ?? '#64748b',
                        'is_system' => $roleName === 'owner',
                        'is_active' => true,
                        'guard_name' => 'web',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('roles')
                        ->where('id', (int) $existingId)
                        ->update([
                            'label' => $definition['label'] ?? ucfirst($roleName),
                            'description' => $definition['description'] ?? null,
                            'color' => $definition['color'] ?? '#64748b',
                            'is_active' => true,
                            'updated_at' => now(),
                        ]);
                }

                DB::table('role_has_permissions')
                    ->where('role_id', (int) $existingId)
                    ->delete();

                $permissionNames = $defaultRolePermissions[$roleName] ?? [];
                if ($permissionNames === ['*']) {
                    $permissionIds = $permissionsByName->pluck('id')->map(fn ($id) => (int) $id)->all();
                } else {
                    $permissionIds = collect($permissionNames)
                        ->map(fn ($name) => $permissionsByName[$name]->id ?? null)
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->values()
                        ->all();
                }

                foreach ($permissionIds as $permissionId) {
                    DB::table('role_has_permissions')->updateOrInsert([
                        'permission_id' => $permissionId,
                        'role_id' => (int) $existingId,
                    ], []);
                }
            }
        }
    }

    private function backfillMembershipRoleIds(): void
    {
        if (!Schema::hasTable('tenant_user_memberships')) {
            return;
        }

        $memberships = DB::table('tenant_user_memberships')
            ->select(['id', 'tenant_id', 'role_in_tenant'])
            ->get();

        foreach ($memberships as $membership) {
            $roleId = DB::table('roles')
                ->where('tenant_id', (int) $membership->tenant_id)
                ->where('name', (string) $membership->role_in_tenant)
                ->where('guard_name', 'web')
                ->value('id');

            if ($roleId) {
                DB::table('tenant_user_memberships')
                    ->where('id', (int) $membership->id)
                    ->update(['role_id' => (int) $roleId]);
            }
        }
    }

    private function backfillInvitationRoleIds(): void
    {
        if (!Schema::hasTable('user_invitations')) {
            return;
        }

        $invitations = DB::table('user_invitations')
            ->select(['id', 'tenant_id', 'role_in_tenant'])
            ->get();

        foreach ($invitations as $invitation) {
            $roleId = DB::table('roles')
                ->where('tenant_id', (int) $invitation->tenant_id)
                ->where('name', (string) $invitation->role_in_tenant)
                ->where('guard_name', 'web')
                ->value('id');

            if ($roleId) {
                DB::table('user_invitations')
                    ->where('id', (int) $invitation->id)
                    ->update(['role_id' => (int) $roleId]);
            }
        }
    }

    private function syncModelHasRoles(): void
    {
        if (!Schema::hasTable('model_has_roles') || !Schema::hasTable('tenant_user_memberships')) {
            return;
        }

        $userModel = \App\Models\User::class;

        $legacyRoleIds = DB::table('roles')
            ->whereNull('tenant_id')
            ->whereIn('name', array_keys(config('rbac.default_roles', [])))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($legacyRoleIds !== []) {
            DB::table('model_has_roles')
                ->where('model_type', $userModel)
                ->whereIn('role_id', $legacyRoleIds)
                ->delete();
        }

        $memberships = DB::table('tenant_user_memberships')
            ->whereNotNull('role_id')
            ->where('status', 'active')
            ->get(['user_id', 'tenant_id', 'role_id']);

        foreach ($memberships as $membership) {
            $tenantRoleIds = DB::table('roles')
                ->where('tenant_id', (int) $membership->tenant_id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($tenantRoleIds !== []) {
                DB::table('model_has_roles')
                    ->where('model_type', $userModel)
                    ->where('model_id', (int) $membership->user_id)
                    ->whereIn('role_id', $tenantRoleIds)
                    ->delete();
            }

            DB::table('model_has_roles')->updateOrInsert([
                'role_id' => (int) $membership->role_id,
                'model_type' => $userModel,
                'model_id' => (int) $membership->user_id,
            ], []);
        }
    }
};
