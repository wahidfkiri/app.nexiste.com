<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('google_calendar_events')) {
            return;
        }

        Schema::table('google_calendar_events', function (Blueprint $table) {
            if (!Schema::hasColumn('google_calendar_events', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('location');
                $table->index(['tenant_id', 'client_id'], 'gcal_evt_tenant_client_idx');
            }

            if (!Schema::hasColumn('google_calendar_events', 'client_name')) {
                $table->string('client_name', 255)->nullable()->after('client_id');
            }

            if (!Schema::hasColumn('google_calendar_events', 'source_type')) {
                $table->string('source_type', 60)->nullable()->after('client_name');
                $table->index(['tenant_id', 'source_type', 'source_id'], 'gcal_evt_tenant_source_idx');
            }

            if (!Schema::hasColumn('google_calendar_events', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }

            if (!Schema::hasColumn('google_calendar_events', 'source_label')) {
                $table->string('source_label', 255)->nullable()->after('source_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('google_calendar_events')) {
            return;
        }

        Schema::table('google_calendar_events', function (Blueprint $table) {
            if (Schema::hasColumn('google_calendar_events', 'client_id')) {
                $table->dropIndex('gcal_evt_tenant_client_idx');
                $table->dropColumn('client_id');
            }

            if (Schema::hasColumn('google_calendar_events', 'client_name')) {
                $table->dropColumn('client_name');
            }

            if (Schema::hasColumn('google_calendar_events', 'source_type')) {
                $table->dropIndex('gcal_evt_tenant_source_idx');
                $table->dropColumn('source_type');
            }

            if (Schema::hasColumn('google_calendar_events', 'source_id')) {
                $table->dropColumn('source_id');
            }

            if (Schema::hasColumn('google_calendar_events', 'source_label')) {
                $table->dropColumn('source_label');
            }
        });
    }
};

