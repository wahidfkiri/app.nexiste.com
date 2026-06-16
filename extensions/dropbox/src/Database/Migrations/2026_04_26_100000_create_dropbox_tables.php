<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dropbox_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->onDelete('cascade');
            $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->string('dropbox_account_id', 100)->nullable();
            $table->string('dropbox_email', 255)->nullable();
            $table->string('dropbox_name', 255)->nullable();
            $table->string('dropbox_avatar_url', 1000)->nullable();
            $table->string('dropbox_root_id', 100)->nullable();
            $table->string('dropbox_root_path', 500)->nullable();
            $table->decimal('space_quota_total_gb', 10, 2)->nullable();
            $table->decimal('space_quota_used_gb', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('dropbox_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('dropbox_id', 100);
            $table->string('parent_path_lower', 500)->nullable();
            $table->string('path_lower', 500)->nullable();
            $table->string('path_display', 500)->nullable();
            $table->string('rev', 100)->nullable();
            $table->string('name', 500);
            $table->string('mime_type', 200)->nullable();
            $table->boolean('is_folder')->default(false);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('web_view_link', 1000)->nullable();
            $table->string('download_link', 1000)->nullable();
            $table->string('thumbnail_link', 1000)->nullable();
            $table->string('shared_link', 1000)->nullable();
            $table->boolean('is_shared')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('client_modified_at')->nullable();
            $table->timestamp('server_modified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'dropbox_id']);
            $table->index(['tenant_id', 'parent_path_lower']);
            $table->index(['tenant_id', 'path_lower']);
            $table->index(['tenant_id', 'is_folder']);
        });

        Schema::create('dropbox_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('dropbox_file_id', 100)->nullable();
            $table->string('file_name', 500)->nullable();
            $table->string('action', 50);
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'action', 'created_at']);
            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dropbox_activity_logs');
        Schema::dropIfExists('dropbox_files');
        Schema::dropIfExists('dropbox_tokens');
    }
};
