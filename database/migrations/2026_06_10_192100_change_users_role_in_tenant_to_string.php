<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'role_in_tenant')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY `role_in_tenant` VARCHAR(50) NOT NULL DEFAULT 'user'");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'role_in_tenant')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $legacyRoles = ['owner', 'admin', 'manager', 'user'];
        $hasCustomRoles = DB::table('users')
            ->whereNotNull('role_in_tenant')
            ->whereNotIn('role_in_tenant', $legacyRoles)
            ->exists();

        if ($hasCustomRoles) {
            return;
        }

        DB::statement("ALTER TABLE `users` MODIFY `role_in_tenant` ENUM('owner', 'admin', 'manager', 'user') NOT NULL DEFAULT 'user'");
    }
};
