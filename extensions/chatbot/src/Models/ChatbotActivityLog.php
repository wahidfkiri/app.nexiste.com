<?php

namespace NexusExtensions\Chatbot\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class ChatbotActivityLog extends Model
{
    protected $table = 'chatbot_activity_logs';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'room_id',
        'message_id',
        'action',
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

    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatbotRoom::class, 'room_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatbotMessage::class, 'message_id');
    }
}
