<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        foreach (['super_admin', 'super-admin'] as $roleName) {
            DB::table('roles')->updateOrInsert(
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
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        DB::table('roles')
            ->whereNull('tenant_id')
            ->where('name', 'super-admin')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
