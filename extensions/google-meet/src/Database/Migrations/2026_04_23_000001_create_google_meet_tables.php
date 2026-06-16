<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_meet_tokens', function (Blueprint $table) {
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

            $table->string('selected_calendar_id', 255)->nullable();
            $table->string('selected_calendar_summary', 255)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'is_active'], 'gmeet_tok_tenant_active_idx');
        });

        Schema::create('google_meet_calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            $table->string('calendar_id', 255);
            $table->string('summary', 255);
            $table->text('description')->nullable();
            $table->string('timezone', 80)->nullable();
            $table->string('background_color', 20)->nullable();
            $table->string('foreground_color', 20)->nullable();
            $table->string('access_role', 50)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_selected')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->string('etag', 255)->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'calendar_id'], 'gmeet_cal_tenant_calendar_unq');
            $table->index(['tenant_id', 'is_selected'], 'gmeet_cal_tenant_selected_idx');
        });

        Schema::create('google_meet_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            $table->string('google_calendar_id', 255);
            $table->string('google_event_id', 255);
            $table->string('ical_uid', 255)->nullable();

            $table->string('summary', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('location', 500)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('visibility', 40)->nullable();
            $table->string('html_link', 1000)->nullable();
            $table->string('meet_link', 1000)->nullable();
            $table->string('conference_id', 120)->nullable();
            $table->string('conference_type', 80)->nullable();

            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->string('start_timezone', 80)->nullable();
            $table->string('end_timezone', 80)->nullable();

            $table->json('attendees')->nullable();
            $table->json('conference_data')->nullable();
            $table->json('metadata')->nullable();

            $table->string('organizer_email', 255)->nullable();
            $table->string('organizer_name', 255)->nullable();
            $table->string('creator_email', 255)->nullable();
            $table->string('creator_name', 255)->nullable();

            $table->unsignedInteger('sequence')->nullable();
            $table->string('etag', 255)->nullable();
            $table->timestamp('google_created_at')->nullable();
            $table->timestamp('google_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->boolean('is_deleted')->default(false);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['tenant_id', 'google_calendar_id', 'google_event_id'], 'gmeet_meet_tenant_cal_event_unq');
            $table->index(['tenant_id', 'start_at'], 'gmeet_meet_tenant_start_idx');
            $table->index(['tenant_id', 'is_deleted'], 'gmeet_meet_tenant_deleted_idx');
        });

        Schema::create('google_meet_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('calendar_id', 255)->nullable();
            $table->string('event_id', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'action', 'created_at'], 'gmeet_log_tenant_action_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_meet_activity_logs');
        Schema::dropIfExists('google_meet_meetings');
        Schema::dropIfExists('google_meet_calendars');
        Schema::dropIfExists('google_meet_tokens');
    }
};
