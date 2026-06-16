<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name', 120)->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name', 120)->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users', 'auth_provider')) {
                $table->string('auth_provider', 30)->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'auth_provider_id')) {
                $table->string('auth_provider_id', 191)->nullable()->after('auth_provider');
                $table->index('auth_provider_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'auth_provider_id')) {
                $table->dropIndex(['auth_provider_id']);
                $table->dropColumn('auth_provider_id');
            }
            if (Schema::hasColumn('users', 'auth_provider')) {
                $table->dropColumn('auth_provider');
            }
            if (Schema::hasColumn('users', 'last_name')) {
                $table->dropColumn('last_name');
            }
            if (Schema::hasColumn('users', 'first_name')) {
                $table->dropColumn('first_name');
            }
        });
    }
};

