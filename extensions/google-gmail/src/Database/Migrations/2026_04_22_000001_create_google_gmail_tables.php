<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_gmail_tokens', function (Blueprint $table) {
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
            $table->string('history_id', 120)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'is_active'], 'ggmail_tok_tenant_active_idx');
        });

        Schema::create('google_gmail_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            $table->string('gmail_message_id', 190);
            $table->string('thread_id', 190)->nullable();
            $table->string('message_id_header', 255)->nullable();
            $table->string('subject', 500)->nullable();
            $table->string('sender', 500)->nullable();
            $table->json('to_recipients')->nullable();
            $table->json('cc_recipients')->nullable();
            $table->text('snippet')->nullable();
            $table->longText('body_text')->nullable();
            $table->longText('body_html')->nullable();
            $table->json('label_ids')->nullable();
            $table->boolean('has_attachments')->default(false);
            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('gmail_internal_date')->nullable();
            $table->string('web_url', 1000)->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['tenant_id', 'gmail_message_id'], 'ggmail_msg_tenant_message_unq');
            $table->index(['tenant_id', 'thread_id'], 'ggmail_msg_tenant_thread_idx');
            $table->index(['tenant_id', 'is_read', 'is_starred'], 'ggmail_msg_tenant_flags_idx');
            $table->index(['tenant_id', 'sent_at'], 'ggmail_msg_tenant_sent_idx');
        });

        Schema::create('google_gmail_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('label_id', 190);
            $table->string('name', 255);
            $table->string('type', 50)->nullable();
            $table->unsignedInteger('messages_total')->default(0);
            $table->unsignedInteger('messages_unread')->default(0);
            $table->unsignedInteger('threads_total')->default(0);
            $table->unsignedInteger('threads_unread')->default(0);
            $table->string('color_background', 20)->nullable();
            $table->string('color_text', 20)->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'label_id'], 'ggmail_lbl_tenant_label_unq');
            $table->index(['tenant_id', 'type'], 'ggmail_lbl_tenant_type_idx');
        });

        Schema::create('google_gmail_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('gmail_message_id', 190)->nullable();
            $table->string('thread_id', 190)->nullable();
            $table->string('action', 80);
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'action', 'created_at'], 'ggmail_log_tenant_action_idx');
            $table->index(['tenant_id', 'user_id'], 'ggmail_log_tenant_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_gmail_activity_logs');
        Schema::dropIfExists('google_gmail_labels');
        Schema::dropIfExists('google_gmail_messages');
        Schema::dropIfExists('google_gmail_tokens');
    }
};
