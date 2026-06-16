<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_event', 150);
            $table->string('source_type', 180)->nullable();
            $table->string('source_id', 100)->nullable();
            $table->string('type', 120);
            $table->string('label', 191);
            $table->decimal('confidence', 5, 2)->default(0.50);
            $table->json('payload')->nullable();
            $table->json('meta')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('dedupe_key', 191)->nullable();
            $table->string('pending_dedupe_key', 191)->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'source_event']);
            $table->index(['tenant_id', 'type']);
            $table->index(['tenant_id', 'source_type', 'source_id'], 'automation_suggestions_source_idx');
        });

        Schema::create('automation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('triggered_by_suggestion_id')->nullable()->constrained('automation_suggestions')->nullOnDelete();
            $table->string('event_name', 191);
            $table->string('action_type', 120);
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->string('status', 20)->default('queued');
            $table->string('idempotency_key', 191)->nullable()->unique();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'event_name']);
            $table->index(['tenant_id', 'action_type']);
        });

        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('automation_event_id')->nullable()->constrained('automation_events')->nullOnDelete();
            $table->foreignId('automation_suggestion_id')->nullable()->constrained('automation_suggestions')->nullOnDelete();
            $table->string('event_name', 191)->nullable();
            $table->string('action_type', 120)->nullable();
            $table->string('level', 20)->default('info');
            $table->string('status', 20)->nullable();
            $table->text('message')->nullable();
            $table->json('response')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'level']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'event_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
        Schema::dropIfExists('automation_events');
        Schema::dropIfExists('automation_suggestions');
    }
};
