<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notion_pages')) {
            Schema::create('notion_pages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('parent_id')->nullable()->index();
                $table->unsignedBigInteger('client_id')->nullable()->index();
                $table->unsignedBigInteger('owner_id')->index();
                $table->string('title', 220);
                $table->string('slug', 260);
                $table->string('icon', 20)->nullable();
                $table->string('cover_color', 20)->nullable();
                $table->string('visibility', 20)->default('private')->index();
                $table->longText('content_text')->nullable();
                $table->json('content_json')->nullable();
                $table->boolean('is_favorite')->default(false)->index();
                $table->boolean('is_template')->default(false)->index();
                $table->boolean('is_archived')->default(false)->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedBigInteger('last_edited_by')->nullable()->index();
                $table->timestamp('last_edited_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'slug']);
                $table->index(['tenant_id', 'parent_id', 'sort_order']);
            });
        }

        if (!Schema::hasTable('notion_page_shares')) {
            Schema::create('notion_page_shares', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('notion_page_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->boolean('can_edit')->default(true);
                $table->boolean('can_comment')->default(true);
                $table->boolean('can_share')->default(false);
                $table->unsignedBigInteger('shared_by')->nullable()->index();
                $table->timestamps();

                $table->unique(['notion_page_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('notion_page_activities')) {
            Schema::create('notion_page_activities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('notion_page_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('event', 80)->index();
                $table->string('description', 255);
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notion_page_activities');
        Schema::dropIfExists('notion_page_shares');
        Schema::dropIfExists('notion_pages');
    }
};

