<?php

namespace Vendor\GoogleCalendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class GoogleCalendarCalendar extends Model
{
    protected $table = 'google_calendar_calendars';

    protected $fillable = [
        'tenant_id',
        'calendar_id',
        'summary',
        'description',
        'timezone',
        'background_color',
        'foreground_color',
        'access_role',
        'is_primary',
        'is_holiday',
        'is_selected',
        'is_hidden',
        'is_deleted',
        'etag',
        'synced_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_holiday' => 'boolean',
        'is_selected' => 'boolean',
        'is_hidden' => 'boolean',
        'is_deleted' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
