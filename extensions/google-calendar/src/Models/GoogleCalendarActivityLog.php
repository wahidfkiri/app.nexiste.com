<?php

namespace Vendor\GoogleCalendar\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class GoogleCalendarActivityLog extends Model
{
    protected $table = 'google_calendar_activity_logs';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'calendar_id',
        'event_id',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
