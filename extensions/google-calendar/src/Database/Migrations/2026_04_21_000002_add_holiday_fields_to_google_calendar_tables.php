<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('google_calendar_calendars') && !Schema::hasColumn('google_calendar_calendars', 'is_holiday')) {
            Schema::table('google_calendar_calendars', function (Blueprint $table) {
                $table->boolean('is_holiday')->default(false)->after('is_primary');
                $table->index(['tenant_id', 'is_holiday'], 'gcal_cal_tenant_holiday_idx');
            });
        }

        if (Schema::hasTable('google_calendar_events') && !Schema::hasColumn('google_calendar_events', 'is_holiday')) {
            Schema::table('google_calendar_events', function (Blueprint $table) {
                $table->boolean('is_holiday')->default(false)->after('all_day');
                $table->index(['tenant_id', 'is_holiday', 'start_at'], 'gcal_evt_tenant_holiday_start_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('google_calendar_events') && Schema::hasColumn('google_calendar_events', 'is_holiday')) {
            Schema::table('google_calendar_events', function (Blueprint $table) {
                $table->dropIndex('gcal_evt_tenant_holiday_start_idx');
                $table->dropColumn('is_holiday');
            });
        }

        if (Schema::hasTable('google_calendar_calendars') && Schema::hasColumn('google_calendar_calendars', 'is_holiday')) {
            Schema::table('google_calendar_calendars', function (Blueprint $table) {
                $table->dropIndex('gcal_cal_tenant_holiday_idx');
                $table->dropColumn('is_holiday');
            });
        }
    }
};

