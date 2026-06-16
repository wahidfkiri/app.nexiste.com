<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('contact_name')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'name']);
        });

        Schema::create('stock_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained('stock_suppliers')->nullOnDelete();
            $table->string('sku', 100)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 30)->default('piece');
            $table->decimal('purchase_price', 15, 4)->default(0);
            $table->decimal('sale_price', 15, 4)->default(0);
            $table->decimal('stock_quantity', 12, 4)->default(0);
            $table->decimal('min_stock', 12, 4)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('stock_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('stock_suppliers')->onDelete('restrict');
            $table->string('number', 50);
            $table->string('reference', 100)->nullable();
            $table->enum('status', ['draft', 'ordered', 'received', 'cancelled'])->default('draft');
            $table->date('order_date')->nullable();
            $table->date('expected_date')->nullable();
            $table->date('received_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'number']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('stock_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('stock_orders')->onDelete('cascade');
            $table->foreignId('article_id')->nullable()->constrained('stock_articles')->nullOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 4)->default(1);
            $table->string('unit', 30)->default('piece');
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'stock_order_id')) {
                $table->foreignId('stock_order_id')->nullable()->after('quote_id')->constrained('stock_orders')->nullOnDelete();
            }
        });

        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'stock_order_id')) {
                $table->foreignId('stock_order_id')->nullable()->after('client_id')->constrained('stock_orders')->nullOnDelete();
            }
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_items', 'article_id')) {
                $table->foreignId('article_id')->nullable()->after('invoice_id')->constrained('stock_articles')->nullOnDelete();
            }
        });

        Schema::table('quote_items', function (Blueprint $table) {
            if (!Schema::hasColumn('quote_items', 'article_id')) {
                $table->foreignId('article_id')->nullable()->after('quote_id')->constrained('stock_articles')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            if (Schema::hasColumn('quote_items', 'article_id')) {
                $table->dropConstrainedForeignId('article_id');
            }
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'article_id')) {
                $table->dropConstrainedForeignId('article_id');
            }
        });

        Schema::table('quotes', function (Blueprint $table) {
            if (Schema::hasColumn('quotes', 'stock_order_id')) {
                $table->dropConstrainedForeignId('stock_order_id');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'stock_order_id')) {
                $table->dropConstrainedForeignId('stock_order_id');
            }
        });

        Schema::dropIfExists('stock_order_items');
        Schema::dropIfExists('stock_orders');
        Schema::dropIfExists('stock_articles');
        Schema::dropIfExists('stock_suppliers');
    }
};
