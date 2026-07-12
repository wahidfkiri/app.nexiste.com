<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Ajoute une colonne `uuid` (identifiant public) aux tables dont les ressources
 * sont exposées dans les URLs, SANS toucher aux clés primaires `id` internes ni
 * aux relations SQL existantes.
 *
 * Idempotente : peut être relancée sans risque (vérifie l'existence table/colonne).
 * Les nouveaux modules ajoutent simplement leur table à la liste $tables ci-dessous
 * (ou fournissent leur propre migration réutilisant le même schéma).
 */
return new class extends Migration
{
    /**
     * Tables dont les ressources sont identifiées publiquement par UUID.
     * NB : on EXCLUT volontairement les tables d'identifiants tiers (Google,
     * Notion, etc.) qui utilisent déjà des identifiants externes opaques.
     */
    private function tables(): array
    {
        return [
            // Core métier
            'clients',
            'invoices',
            'quotes',
            'payments',
            'drafts',
            // Stock
            'stock_articles',
            'stock_orders',
            'stock_suppliers',
            'stock_delivery_notes',
            // Projets
            'projects',
            'project_tasks',
            // Utilisateurs / RBAC / Marketplace
            'users',
            'roles',
            'extensions',
            'tenant_extensions',
            // Chatbot
            'chatbot_rooms',
        ];
    }

    public function up(): void
    {
        foreach ($this->tables() as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'uuid')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t): void {
                $t->uuid('uuid')->nullable()->after('id');
            });

            // Backfill des lignes existantes (par lots pour les grosses tables).
            DB::table($table)
                ->select('id')
                ->whereNull('uuid')
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($table): void {
                    foreach ($rows as $row) {
                        DB::table($table)
                            ->where('id', $row->id)
                            ->update(['uuid' => (string) Str::uuid()]);
                    }
                });

            // Index unique une fois toutes les lignes renseignées.
            Schema::table($table, function (Blueprint $t): void {
                $t->unique('uuid');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables() as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'uuid')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t): void {
                $t->dropUnique(['uuid']);
                $t->dropColumn('uuid');
            });
        }
    }
};
