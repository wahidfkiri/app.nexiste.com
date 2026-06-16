<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('number', 50);
            $table->enum('type', ['in', 'out']);
            $table->enum('status', ['draft', 'validated', 'cancelled'])->default('draft');
            $table->foreignId('supplier_id')->nullable()->constrained('stock_suppliers')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('stock_order_id')->nullable()->constrained('stock_orders')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('reference', 100)->nullable();
            $table->date('issue_date')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'number']);
            $table->index(['tenant_id', 'type', 'status']);
            $table->index(['tenant_id', 'issue_date']);
        });

        Schema::create('stock_delivery_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained('stock_delivery_notes')->onDelete('cascade');
            $table->foreignId('article_id')->nullable()->constrained('stock_articles')->nullOnDelete();
            $table->foreignId('stock_order_item_id')->nullable()->constrained('stock_order_items')->nullOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('sku', 100)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 4);
            $table->string('unit', 30)->default('piece');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['delivery_note_id', 'position']);
            $table->index(['article_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('article_id')->constrained('stock_articles')->onDelete('restrict');
            $table->foreignId('delivery_note_id')->nullable()->constrained('stock_delivery_notes')->nullOnDelete();
            $table->foreignId('delivery_note_item_id')->nullable()->constrained('stock_delivery_note_items')->nullOnDelete();
            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->enum('movement_type', ['opening_balance', 'delivery_note_in', 'delivery_note_out', 'delivery_note_reversal', 'adjustment', 'return']);
            $table->enum('direction', ['in', 'out']);
            $table->decimal('quantity', 12, 4);
            $table->string('unit', 30)->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamp('happened_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'article_id', 'happened_at']);
            $table->index(['tenant_id', 'movement_type']);
            $table->index(['tenant_id', 'direction']);
            $table->index(['source_type', 'source_id']);
            $table->index(['reference']);
        });

        $now = now();
        $legacyArticles = DB::table('stock_articles')
            ->select(['id', 'tenant_id', 'user_id', 'sku', 'name', 'unit', 'stock_quantity', 'created_at'])
            ->where('stock_quantity', '>', 0)
            ->orderBy('id')
            ->get();

        if ($legacyArticles->isNotEmpty()) {
            $payload = [];

            foreach ($legacyArticles as $article) {
                $payload[] = [
                    'tenant_id' => $article->tenant_id,
                    'user_id' => $article->user_id,
                    'article_id' => $article->id,
                    'delivery_note_id' => null,
                    'delivery_note_item_id' => null,
                    'source_type' => 'legacy_article_stock',
                    'source_id' => $article->id,
                    'movement_type' => 'opening_balance',
                    'direction' => 'in',
                    'quantity' => $article->stock_quantity,
                    'unit' => $article->unit,
                    'reference' => 'LEGACY-STOCK',
                    'reason' => 'Opening balance migrated from stock_articles.stock_quantity',
                    'happened_at' => $article->created_at ?: $now,
                    'notes' => sprintf('Initial stock migrated for article #%s (%s)', $article->id, $article->sku ?: $article->name),
                    'meta' => json_encode(['legacy_stock_quantity' => (float) $article->stock_quantity]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($payload, 500) as $chunk) {
                DB::table('stock_movements')->insert($chunk);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_delivery_note_items');
        Schema::dropIfExists('stock_delivery_notes');
    }
};