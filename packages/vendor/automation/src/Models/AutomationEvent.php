<?php

namespace Vendor\Automation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vendor\CrmCore\Models\Tenant;

class AutomationEvent extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'automation_events';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'event_name',
        'action_type',
        'payload',
        'response',
        'status',
        'idempotency_key',
        'triggered_by_suggestion_id',
        'attempts',
        'last_error',
        'dispatched_at',
        'processed_at',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'attempts' => 'integer',
        'dispatched_at' => 'datetime',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function suggestion(): BelongsTo
    {
        return $this->belongsTo(AutomationSuggestion::class, 'triggered_by_suggestion_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AutomationLog::class, 'automation_event_id');
    }
}
