<?php
// filename: 2025_01_01_000005_add_missing_fields_to_clients_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
            // Coordonnées supplémentaires
            $table->string('fax')->nullable()->after('mobile');
            $table->string('address2')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            
            // Informations fiscales supplémentaires
            $table->string('ape_code', 10)->nullable()->after('siret');      // Code APE/NAF (5 caractères + lettre)
            $table->string('rcs')->nullable()->after('ape_code');            // Numéro RCS
            
            // Informations commerciales
            $table->string('industry')->nullable()->after('potential_value'); // Secteur d'activité
            $table->integer('employee_count')->unsigned()->nullable()->after('industry');
            $table->string('payment_term')->nullable()->after('employee_count'); // Ex: 'net_30', 'net_60'
            
            // Modification de l'enum 'type' pour ajouter les nouvelles valeurs
            // MySQL / PostgreSQL
            $table->enum('type', ['entreprise', 'particulier', 'startup', 'association', 'public'])
                  ->default('entreprise')
                  ->change();
            
            // Modification de l'enum 'status' pour ajouter 'suspendu'
            $table->enum('status', ['actif', 'inactif', 'en_attente', 'suspendu'])
                  ->default('actif')
                  ->change();
        });
    }

    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'fax',
                'address2',
                'state',
                'ape_code',
                'rcs',
                'industry',
                'employee_count',
                'payment_term',
            ]);
            
            // Restaurer les enum d'origine
            $table->enum('type', ['entreprise', 'particulier', 'startup'])
                  ->default('entreprise')
                  ->change();
            
            $table->enum('status', ['actif', 'inactif', 'en_attente'])
                  ->default('actif')
                  ->change();
        });
    }
};