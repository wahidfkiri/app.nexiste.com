<?php

namespace Vendor\GoogleCalendar\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class GoogleCalendarEvent extends Model
{
    protected $table = 'google_calendar_events';

    protected $fillable = [
        'tenant_id',
        'google_calendar_id',
        'google_event_id',
        'ical_uid',
        'summary',
        'description',
        'location',
        'client_id',
        'client_name',
        'source_type',
        'source_id',
        'source_label',
        'status',
        'visibility',
        'transparency',
        'html_link',
        'color_id',
        'all_day',
        'is_holiday',
        'start_at',
        'end_at',
        'start_timezone',
        'end_timezone',
        'recurrence',
        'attendees',
        'reminders',
        'conference_data',
        'organizer_email',
        'organizer_name',
        'creator_email',
        'creator_name',
        'sequence',
        'etag',
        'google_created_at',
        'google_updated_at',
        'synced_at',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'all_day' => 'boolean',
        'is_holiday' => 'boolean',
        'client_id' => 'integer',
        'source_id' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'recurrence' => 'array',
        'attendees' => 'array',
        'reminders' => 'array',
        'conference_data' => 'array',
        'google_created_at' => 'datetime',
        'google_updated_at' => 'datetime',
        'synced_at' => 'datetime',
        'is_deleted' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
