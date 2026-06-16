<?php

namespace NexusExtensions\Slack\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class SlackActivityLog extends Model
{
    protected $table = 'slack_activity_logs';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'channel_id',
        'message_ts',
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

