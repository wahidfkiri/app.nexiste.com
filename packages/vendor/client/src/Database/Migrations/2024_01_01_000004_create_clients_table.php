<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Informations générales
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('website')->nullable();
            
            // Adresse
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            
            // Informations fiscales
            $table->string('vat_number')->nullable();
            $table->string('siret')->nullable();
            
            // Catégorisation
            $table->enum('type', ['entreprise', 'particulier', 'startup'])->default('entreprise');
            $table->enum('status', ['actif', 'inactif', 'en_attente'])->default('actif');
            
            // Tags (JSON)
            $table->json('tags')->nullable();
            
            // Finances
            $table->decimal('revenue', 15, 2)->default(0);
            $table->decimal('potential_value', 15, 2)->nullable();
            
            // Relation commerciale
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            
            // Autres
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'type']);
            $table->unique(['tenant_id', 'email']);
            $table->index(['assigned_to']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients');
    }
};