<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |----------------------------------------------------------------------
        | IMPORTANT — Architecture multi-tenant avec Spatie Permission
        |----------------------------------------------------------------------
        | Spatie Permission utilise un cache global. Pour l'isolation multi-
        | tenant, on ajoute tenant_id aux rôles et on étend la résolution.
        |
        | Deux stratégies possibles :
        | A) Un seul set de rôles globaux (owner, admin…) — SIMPLE
        | B) Rôles par tenant (chaque tenant peut créer ses propres rôles)
        |
        | Ici on choisit B : rôles tenant-spécifiques + rôles globaux système.
        |----------------------------------------------------------------------
        */

        // Ajouter tenant_id + metadata sur la table roles de Spatie
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('roles', 'label')) {
                $table->string('label', 100)->nullable()->after('name');
            }
            if (!Schema::hasColumn('roles', 'description')) {
                $table->string('description', 255)->nullable()->after('label');
            }
            if (!Schema::hasColumn('roles', 'color')) {
                $table->string('color', 20)->default('#64748b')->after('description');
            }
            if (!Schema::hasColumn('roles', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('color');
            }
            if (!Schema::hasColumn('roles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_system');
            }

            // Index pour les requêtes tenant-aware
            $table->index(['tenant_id', 'name']);
        });

        // Ajouter tenant_id sur les permissions (permissions peuvent être globales ou tenant-specific)
        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('permissions', 'label')) {
                $table->string('label', 150)->nullable()->after('name');
            }
            if (!Schema::hasColumn('permissions', 'group')) {
                $table->string('group', 50)->nullable()->after('label');
            }
            if (!Schema::hasColumn('permissions', 'description')) {
                $table->string('description', 255)->nullable()->after('group');
            }

            $table->index(['tenant_id', 'group']);
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'name']);
            foreach (['tenant_id','label','description','color','is_system','is_active'] as $col) {
                if (Schema::hasColumn('roles', $col)) $table->dropColumn($col);
            }
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'group']);
            foreach (['tenant_id','label','group','description'] as $col) {
                if (Schema::hasColumn('permissions', $col)) $table->dropColumn($col);
            }
        });
    }
};