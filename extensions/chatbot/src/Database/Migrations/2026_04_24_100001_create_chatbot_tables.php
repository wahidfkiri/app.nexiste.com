<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('room_uuid', 60)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('icon', 60)->nullable();
            $table->string('color', 20)->nullable();
            $table->boolean('is_private')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_default')->default(false);
            $table->unsignedBigInteger('messages_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name'], 'chatbot_rooms_tenant_name_unq');
            $table->index(['tenant_id', 'is_archived'], 'chatbot_rooms_tenant_archived_idx');
            $table->index(['tenant_id', 'last_message_at'], 'chatbot_rooms_tenant_last_msg_idx');
        });

        Schema::create('chatbot_room_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('chatbot_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 30)->default('member');
            $table->boolean('is_muted')->default(false);
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['room_id', 'user_id'], 'chatbot_room_members_room_user_unq');
            $table->index(['tenant_id', 'user_id'], 'chatbot_room_members_tenant_user_idx');
        });

        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('chatbot_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reply_to_message_id')->nullable()->constrained('chatbot_messages')->nullOnDelete();

            $table->string('message_uuid', 60)->unique();
            $table->string('message_type', 30)->default('text');
            $table->string('sender_name', 150)->nullable();
            $table->longText('text')->nullable();
            $table->json('attachments')->nullable();
            $table->json('emoji_reactions')->nullable();

            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'room_id', 'sent_at'], 'chatbot_messages_tenant_room_sent_idx');
            $table->index(['tenant_id', 'is_deleted'], 'chatbot_messages_tenant_deleted_idx');
        });

        Schema::create('chatbot_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('chatbot_rooms')->nullOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('chatbot_messages')->nullOnDelete();
            $table->string('action', 80);
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'action', 'created_at'], 'chatbot_logs_tenant_action_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_activity_logs');
        Schema::dropIfExists('chatbot_messages');
        Schema::dropIfExists('chatbot_room_members');
        Schema::dropIfExists('chatbot_rooms');
    }
};
