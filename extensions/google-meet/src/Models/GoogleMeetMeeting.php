<?php

namespace NexusExtensions\GoogleMeet\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class GoogleMeetMeeting extends Model
{
    protected $table = 'google_meet_meetings';

    protected $fillable = [
        'tenant_id',
        'google_calendar_id',
        'google_event_id',
        'ical_uid',
        'summary',
        'description',
        'location',
        'status',
        'visibility',
        'html_link',
        'meet_link',
        'conference_id',
        'conference_type',
        'start_at',
        'end_at',
        'start_timezone',
        'end_timezone',
        'attendees',
        'conference_data',
        'metadata',
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
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'attendees' => 'array',
        'conference_data' => 'array',
        'metadata' => 'array',
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
