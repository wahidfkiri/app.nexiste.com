<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slack_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->onDelete('cascade');
            $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('bot_token')->nullable();
            $table->string('bot_user_id', 100)->nullable();
            $table->string('app_id', 100)->nullable();
            $table->string('team_id', 100)->nullable();
            $table->string('team_name', 255)->nullable();
            $table->string('authed_user_id', 100)->nullable();
            $table->text('scope')->nullable();

            $table->string('selected_channel_id', 100)->nullable();
            $table->string('selected_channel_name', 255)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'is_active'], 'slack_tok_tenant_active_idx');
        });

        Schema::create('slack_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            $table->string('channel_id', 100);
            $table->string('name', 255);
            $table->boolean('is_private')->default(false);
            $table->boolean('is_im')->default(false);
            $table->boolean('is_mpim')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_member')->default(false);
            $table->boolean('is_selected')->default(false);
            $table->unsignedInteger('num_members')->nullable();
            $table->text('topic')->nullable();
            $table->text('purpose')->nullable();
            $table->string('last_message_ts', 40)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->json('raw')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'channel_id'], 'slack_chan_tenant_channel_unq');
            $table->index(['tenant_id', 'is_selected'], 'slack_chan_tenant_selected_idx');
            $table->index(['tenant_id', 'name'], 'slack_chan_tenant_name_idx');
        });

        Schema::create('slack_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            $table->string('channel_id', 100);
            $table->string('slack_ts', 40);
            $table->string('thread_ts', 40)->nullable();

            $table->string('user_id', 100)->nullable();
            $table->string('username', 255)->nullable();
            $table->longText('text')->nullable();
            $table->json('blocks')->nullable();
            $table->json('attachments')->nullable();
            $table->json('reactions')->nullable();
            $table->boolean('is_bot')->default(false);
            $table->boolean('is_deleted')->default(false);

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->json('raw')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['tenant_id', 'channel_id', 'slack_ts'], 'slack_msg_tenant_channel_ts_unq');
            $table->index(['tenant_id', 'channel_id', 'sent_at'], 'slack_msg_tenant_channel_sent_idx');
            $table->index(['tenant_id', 'is_deleted'], 'slack_msg_tenant_deleted_idx');
        });

        Schema::create('slack_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('channel_id', 100)->nullable();
            $table->string('message_ts', 40)->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'action', 'created_at'], 'slack_log_tenant_action_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slack_activity_logs');
        Schema::dropIfExists('slack_messages');
        Schema::dropIfExists('slack_channels');
        Schema::dropIfExists('slack_tokens');
    }
};

