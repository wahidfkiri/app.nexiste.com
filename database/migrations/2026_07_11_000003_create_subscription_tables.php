<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Système d'abonnement (facturation plateforme -> tenant).
 *
 * - subscription_plans        : les forfaits (dont le forfait gratuit / démo).
 * - subscription_plan_prices  : les périodes de chaque forfait (1, 3, 6, 12 mois…)
 *                               avec prix et remise (calcul auto, éditable).
 * - payment_methods           : moyens de paiement (PayPal par défaut, manuel…).
 * - tenant_subscriptions      : l'abonnement en cours de chaque tenant.
 *
 * N'altère aucune table existante. Le tenant possède déjà trial_ends_at /
 * subscription_ends_at, qui restent la source de vérité rapide côté middleware.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_plans')) {
            Schema::create('subscription_plans', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->nullable()->unique();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_free')->default(false);      // forfait démo / gratuit
                $table->unsignedInteger('trial_days')->default(0); // période d'essai (si applicable)
                $table->decimal('monthly_price', 10, 2)->default(0); // prix mensuel de référence (calcul auto)
                $table->string('currency', 3)->default('EUR');
                $table->json('features')->nullable();            // liste d'atouts affichés sur la carte
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('subscription_plan_prices')) {
            Schema::create('subscription_plan_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
                $table->unsignedInteger('period_months');         // 1, 3, 6, 12…
                $table->decimal('price', 10, 2)->default(0);      // prix total de la période (éditable)
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['plan_id', 'period_months']);
            });
        }

        if (! Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->nullable()->unique();
                $table->string('name');
                $table->string('provider');                       // paypal | manual | stripe …
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->json('config')->nullable();               // config non sensible (les secrets restent dans .env)
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tenant_subscriptions')) {
            Schema::create('tenant_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->nullable()->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
                $table->foreignId('plan_price_id')->nullable()->constrained('subscription_plan_prices')->nullOnDelete();
                $table->string('payment_method')->nullable();     // provider utilisé
                $table->string('status')->default('pending');     // pending | trialing | active | expired | cancelled
                $table->boolean('is_trial')->default(false);
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('currency', 3)->default('EUR');
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->timestamp('reminder_sent_at')->nullable(); // rappel J-7 déjà envoyé
                $table->json('meta')->nullable();                  // référence transaction, etc.
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index('ends_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('subscription_plan_prices');
        Schema::dropIfExists('subscription_plans');
    }
};
