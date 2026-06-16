<?php

namespace Vendor\Automation\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class AutomationLog extends Model
{
    protected $table = 'automation_logs';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'automation_event_id',
        'automation_suggestion_id',
        'event_name',
        'action_type',
        'level',
        'status',
        'message',
        'response',
        'context',
    ];

    protected $casts = [
        'response' => 'array',
        'context' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function automationEvent(): BelongsTo
    {
        return $this->belongsTo(AutomationEvent::class, 'automation_event_id');
    }

    public function suggestion(): BelongsTo
    {
        return $this->belongsTo(AutomationSuggestion::class, 'automation_suggestion_id');
    }
}
