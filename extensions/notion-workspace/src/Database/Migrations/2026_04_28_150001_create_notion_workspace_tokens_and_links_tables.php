<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notion_workspace_tokens')) {
            Schema::create('notion_workspace_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->unique();
                $table->unsignedBigInteger('connected_by')->nullable()->index();
                $table->text('access_token');
                $table->text('refresh_token')->nullable();
                $table->timestamp('token_expires_at')->nullable();
                $table->string('notion_workspace_id', 100)->nullable()->index();
                $table->string('notion_workspace_name', 255)->nullable();
                $table->text('notion_workspace_icon')->nullable();
                $table->string('notion_bot_id', 100)->nullable()->index();
                $table->string('notion_owner_type', 50)->nullable();
                $table->string('notion_user_id', 100)->nullable()->index();
                $table->string('notion_user_name', 255)->nullable();
                $table->string('notion_user_email', 255)->nullable();
                $table->text('notion_user_avatar_url')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('connected_at')->nullable();
                $table->timestamp('disconnected_at')->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('notion_page_links')) {
            Schema::create('notion_page_links', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('notion_page_id', 100)->index();
                $table->string('notion_parent_id', 100)->nullable()->index();
                $table->string('notion_page_title', 255);
                $table->text('notion_page_url')->nullable();
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->unsignedBigInteger('project_id')->nullable()->index();
                $table->string('context_label', 120)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('linked_by')->nullable()->index();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'notion_page_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notion_page_links');
        Schema::dropIfExists('notion_workspace_tokens');
    }
};