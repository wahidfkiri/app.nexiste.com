<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── CATALOGUE D'EXTENSIONS (géré par super-admin) ──────────────────
        Schema::create('extensions', function (Blueprint $table) {
            $table->id();

            // Identité
            $table->string('slug', 100)->unique();          // 'google-drive', 'slack'
            $table->string('name', 150);
            $table->string('tagline', 255)->nullable();     // Court slogan
            $table->text('description')->nullable();
            $table->text('long_description')->nullable();   // Markdown supporté
            $table->string('version', 20)->default('1.0.0');

            // Catégorie & visuel
            $table->string('category', 50)->default('other');
            $table->string('icon')->nullable();             // Chemin fichier ou classe FA
            $table->string('icon_bg_color', 20)->default('#3b82f6');
            $table->string('banner')->nullable();           // Image banner

            // Éditeur
            $table->string('developer_name', 150)->nullable();
            $table->string('developer_url', 255)->nullable();
            $table->string('documentation_url', 255)->nullable();
            $table->string('support_url', 255)->nullable();

            // Tarification
            $table->enum('pricing_type', ['free','paid','freemium','trial','per_seat','usage'])->default('free');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 5)->default('EUR');
            $table->enum('billing_cycle', ['monthly','yearly','lifetime','once'])->nullable();
            $table->decimal('yearly_price', 10, 2)->nullable();    // Prix annuel si différent
            $table->boolean('has_trial')->default(false);
            $table->unsignedSmallInteger('trial_days')->default(14);

            // Statut & visibilité
            $table->enum('status', ['active','inactive','deprecated','beta','coming_soon'])->default('active');
            $table->boolean('is_featured')->default(false);         // Mis en avant
            $table->boolean('is_new')->default(false);
            $table->boolean('is_verified')->default(false);         // Vérifié par équipe
            $table->boolean('is_official')->default(false);         // Extension officielle
            $table->unsignedInteger('sort_order')->default(0);

            // Compatibilité
            $table->json('compatible_modules')->nullable();         // ['invoices','clients']
            $table->json('required_permissions')->nullable();       // Permissions nécessaires
            $table->json('settings_schema')->nullable();            // Schéma JSON des paramètres
            $table->json('screenshots')->nullable();                // URLs screenshots

            // Stats (dénormalisées pour perf)
            $table->unsignedInteger('installs_count')->default(0);
            $table->unsignedInteger('active_installs_count')->default(0);
            $table->decimal('rating', 3, 1)->default(0);
            $table->unsignedInteger('ratings_count')->default(0);

            // Webhook
            $table->string('webhook_url', 255)->nullable();
            $table->string('webhook_secret', 100)->nullable();

            // Méta
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index(['category', 'status']);
            $table->index(['status', 'is_featured']);
            $table->index(['pricing_type', 'status']);
        });

        // ── ACTIVATIONS PAR TENANT (pivot enrichi) ─────────────────────────
        Schema::create('tenant_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')
                  ->constrained('tenants')
                  ->onDelete('cascade');
            $table->foreignId('extension_id')
                  ->constrained('extensions')
                  ->onDelete('cascade');
            $table->foreignId('activated_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Statut de l'activation
            $table->enum('status', ['active','inactive','suspended','pending','trial','expired'])
                  ->default('pending');

            // Dates
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();

            // Configuration propre au tenant pour cette extension
            $table->json('settings')->nullable();
            $table->json('credentials')->nullable();        // Tokens/API keys chiffrés
            $table->string('api_key', 128)->nullable();     // Clé API spécifique au tenant

            // Facturation
            $table->enum('billing_cycle', ['monthly','yearly','lifetime','once'])->nullable();
            $table->decimal('price_paid', 10, 2)->default(0);
            $table->string('currency', 5)->default('EUR');
            $table->string('payment_reference', 100)->nullable();

            // Suspension
            $table->text('suspension_reason')->nullable();
            $table->string('suspended_by')->nullable();

            // Usage tracking
            $table->unsignedInteger('api_calls_count')->default(0);
            $table->timestamp('last_used_at')->nullable();

            // Notes internes (super-admin)
            $table->text('internal_notes')->nullable();

            $table->timestamps();

            // Contrainte unicité
            $table->unique(['tenant_id', 'extension_id'], 'tenant_ext_unique');

            // Index
            $table->index(['tenant_id', 'status']);
            $table->index(['extension_id', 'status']);
            $table->index(['status', 'trial_ends_at']);
        });

        // ── AVIS ET NOTES ──────────────────────────────────────────────────
        Schema::create('extension_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_id')->constrained('extensions')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('rating');           // 1-5
            $table->string('title', 150)->nullable();
            $table->text('body')->nullable();
            $table->boolean('is_verified_purchase')->default(true);
            $table->boolean('is_approved')->default(false);
            $table->timestamps();

            $table->unique(['extension_id', 'tenant_id']);
            $table->index(['extension_id', 'is_approved', 'rating']);
        });

        // ── HISTORIQUE DES ACTIVATIONS ─────────────────────────────────────
        Schema::create('extension_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_id')->constrained('extensions')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 80);                     // 'activated','deactivated','configured'…
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'extension_id', 'event']);
            $table->index(['extension_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_activity_logs');
        Schema::dropIfExists('extension_reviews');
        Schema::dropIfExists('tenant_extensions');
        Schema::dropIfExists('extensions');
    }
};