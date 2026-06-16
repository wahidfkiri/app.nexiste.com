<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── DEVISES PERSONNALISÉES PAR TENANT ─────────────────────────────
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('code', 10);          // ISO 4217 : EUR, USD…
            $table->string('name', 100);
            $table->string('symbol', 10);
            $table->enum('symbol_position', ['before','after'])->default('after');
            $table->unsignedTinyInteger('decimals')->default(2);
            $table->string('thousands_sep', 5)->default(' ');
            $table->string('decimal_sep', 5)->default(',');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id','code']);
            $table->index(['tenant_id','is_default']);
        });

        // ─── TAUX TVA PAR TENANT ───────────────────────────────────────────
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name', 100);          // ex : TVA 20%
            $table->decimal('rate', 5, 2);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tenant_id','is_active']);
        });

        // ─── DEVIS ─────────────────────────────────────────────────────────
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('restrict');
            $table->string('number', 50)->unique();
            $table->string('reference', 100)->nullable();
            $table->enum('status', ['draft','sent','viewed','accepted','declined','expired'])->default('draft');

            // Devise
            $table->string('currency', 10)->default('EUR');
            $table->decimal('exchange_rate', 12, 6)->default(1);

            // Dates
            $table->date('issue_date');
            $table->date('valid_until')->nullable();

            // Calculs
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->enum('discount_type', ['percent','fixed','none'])->default('none');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('withholding_tax_rate', 5, 2)->default(0);
            $table->decimal('withholding_tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            // Textes
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->text('footer')->nullable();
            $table->text('internal_notes')->nullable();

            // Suivi
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->text('decline_reason')->nullable();
            $table->unsignedBigInteger('converted_to_invoice_id')->nullable();

            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index(['tenant_id','status']);
            $table->index(['tenant_id','client_id']);
            $table->index(['tenant_id','issue_date']);
            $table->index(['tenant_id','valid_until']);
        });

        // ─── LIGNES DE DEVIS ───────────────────────────────────────────────
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->onDelete('cascade');
            $table->unsignedSmallInteger('position')->default(0);
            $table->text('description');
            $table->string('reference', 100)->nullable();
            $table->decimal('quantity', 10, 4)->default(1);
            $table->string('unit', 30)->nullable();           // pièce, heure, jour…
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->enum('discount_type', ['percent','fixed','none'])->default('none');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
            $table->index(['quote_id','position']);
        });

        // ─── FACTURES ──────────────────────────────────────────────────────
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('restrict');
            $table->foreignId('quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->string('number', 50)->unique();
            $table->string('reference', 100)->nullable();
            $table->enum('status', ['draft','sent','viewed','partial','paid','overdue','cancelled','refunded'])->default('draft');

            // Devise
            $table->string('currency', 10)->default('EUR');
            $table->decimal('exchange_rate', 12, 6)->default(1);

            // Dates
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->date('payment_date')->nullable();

            // Paiement
            $table->string('payment_method', 50)->nullable();
            $table->unsignedSmallInteger('payment_terms')->default(30); // jours

            // Calculs
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->enum('discount_type', ['percent','fixed','none'])->default('none');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('withholding_tax_rate', 5, 2)->default(0);
            $table->decimal('withholding_tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('amount_due', 15, 2)->default(0);

            // Textes
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->text('footer')->nullable();
            $table->text('internal_notes')->nullable();

            // Suivi
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->unsignedTinyInteger('reminder_count')->default(0);
            $table->timestamp('last_reminder_at')->nullable();

            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index(['tenant_id','status']);
            $table->index(['tenant_id','client_id']);
            $table->index(['tenant_id','issue_date']);
            $table->index(['tenant_id','due_date']);
            $table->index(['tenant_id','currency']);
        });

        // ─── LIGNES DE FACTURES ────────────────────────────────────────────
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->unsignedSmallInteger('position')->default(0);
            $table->text('description');
            $table->string('reference', 100)->nullable();
            $table->decimal('quantity', 10, 4)->default(1);
            $table->string('unit', 30)->nullable();
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->enum('discount_type', ['percent','fixed','none'])->default('none');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
            $table->index(['invoice_id','position']);
        });

        // ─── PAIEMENTS ─────────────────────────────────────────────────────
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('EUR');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->decimal('amount_base_currency', 15, 2)->nullable();
            $table->date('payment_date');
            $table->string('payment_method', 50);
            $table->string('reference', 100)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account', 50)->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id','invoice_id']);
            $table->index(['tenant_id','payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('currencies');
    }
};
