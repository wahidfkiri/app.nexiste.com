<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trello_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('api_token');
            $table->json('scopes')->nullable();
            $table->string('token_expiration', 20)->default('30days');
            $table->timestamp('token_expires_at')->nullable();
            $table->string('trello_member_id')->nullable();
            $table->string('trello_username')->nullable();
            $table->string('trello_full_name')->nullable();
            $table->string('trello_avatar_url')->nullable();
            $table->string('trello_profile_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique('tenant_id');
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('trello_boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('trello_id', 64);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('url')->nullable();
            $table->string('workspace_id')->nullable();
            $table->string('background_color', 20)->nullable();
            $table->string('background_image_url')->nullable();
            $table->boolean('closed')->default(false);
            $table->boolean('starred')->default(false);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'trello_id']);
            $table->index(['tenant_id', 'closed']);
            $table->index(['tenant_id', 'last_activity_at']);
        });

        Schema::create('trello_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('trello_board_id')->constrained('trello_boards')->cascadeOnDelete();
            $table->string('trello_id', 64);
            $table->string('name');
            $table->decimal('position', 20, 4)->nullable();
            $table->boolean('closed')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'trello_id']);
            $table->index(['trello_board_id', 'closed']);
        });

        Schema::create('trello_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('trello_board_id')->constrained('trello_boards')->cascadeOnDelete();
            $table->foreignId('trello_list_id')->nullable()->constrained('trello_lists')->nullOnDelete();
            $table->string('trello_id', 64);
            $table->string('name');
            $table->longText('description')->nullable();
            $table->string('url')->nullable();
            $table->string('short_url')->nullable();
            $table->decimal('position', 20, 4)->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->boolean('closed')->default(false);
            $table->json('labels')->nullable();
            $table->json('members')->nullable();
            $table->json('badges')->nullable();
            $table->string('cover_color', 40)->nullable();
            $table->string('cover_image_url')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'trello_id']);
            $table->index(['trello_list_id', 'closed']);
            $table->index(['trello_board_id', 'last_activity_at']);
        });

        Schema::create('trello_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('trello_card_id')->constrained('trello_cards')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'trello_card_id']);
            $table->index(['tenant_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trello_links');
        Schema::dropIfExists('trello_cards');
        Schema::dropIfExists('trello_lists');
        Schema::dropIfExists('trello_boards');
        Schema::dropIfExists('trello_tokens');
    }
};
