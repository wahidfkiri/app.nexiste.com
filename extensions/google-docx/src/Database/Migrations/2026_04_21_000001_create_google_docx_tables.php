<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_docx_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->onDelete('cascade');
            $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            $table->string('google_account_id', 120)->nullable();
            $table->string('google_email', 255)->nullable();
            $table->string('google_name', 255)->nullable();
            $table->string('google_avatar_url', 1000)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'is_active'], 'gdocx_tok_tenant_active_idx');
        });

        Schema::create('google_docx_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            $table->string('document_id', 150);
            $table->string('title', 500);
            $table->string('document_url', 1000)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->boolean('is_shared')->default(false);

            $table->unsignedBigInteger('revision_id')->nullable();
            $table->unsignedInteger('content_chars')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('drive_created_at')->nullable();
            $table->timestamp('drive_modified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'document_id'], 'gdocx_doc_tenant_id_unq');
            $table->index(['tenant_id'], 'gdocx_doc_tenant_idx');
        });

        Schema::create('google_docx_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_id', 150)->nullable();
            $table->string('document_title', 500)->nullable();
            $table->string('action', 80);
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'action', 'created_at'], 'gdocx_log_tenant_action_idx');
            $table->index(['tenant_id', 'user_id'], 'gdocx_log_tenant_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_docx_activity_logs');
        Schema::dropIfExists('google_docx_documents');
        Schema::dropIfExists('google_docx_tokens');
    }
};
