<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('type', 120);
            $table->json('data');
            $table->string('route', 500)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tenant_id', 'type', 'updated_at'], 'drafts_actor_type_updated_idx');
            $table->index(['tenant_id', 'expires_at'], 'drafts_tenant_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drafts');
    }
};
