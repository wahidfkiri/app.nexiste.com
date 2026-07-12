<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modèle mono-devise : la devise d'un document (facture, devis, paiement) doit
 * toujours refléter la devise principale de son tenant (paramètres généraux).
 *
 * Les anciens enregistrements ont été figés sur la valeur par défaut 'EUR' à la
 * création. Cette migration réaligne leur devise stockée sur celle du tenant,
 * afin que les documents générés (PDF, page « voir ») affichent la bonne devise.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants') || ! Schema::hasColumn('tenants', 'currency')) {
            return;
        }

        $tenants = DB::table('tenants')
            ->whereNotNull('currency')
            ->where('currency', '!=', '')
            ->get(['id', 'currency']);

        $tables = ['invoices', 'quotes', 'payments'];

        foreach ($tenants as $tenant) {
            $currency = strtoupper((string) $tenant->currency);

            foreach ($tables as $table) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'currency')) {
                    continue;
                }

                DB::table($table)
                    ->where('tenant_id', $tenant->id)
                    ->where('currency', '!=', $currency)
                    ->update(['currency' => $currency]);
            }
        }
    }

    public function down(): void
    {
        // Pas de rollback : la devise d'origine figée n'est pas conservée.
    }
};
