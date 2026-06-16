<?php

namespace NexusExtensions\Chatbot\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class ChatbotMessage extends Model
{
    protected $table = 'chatbot_messages';

    protected $fillable = [
        'tenant_id',
        'room_id',
        'user_id',
        'reply_to_message_id',
        'message_uuid',
        'message_type',
        'sender_name',
        'text',
        'attachments',
        'emoji_reactions',
        'is_edited',
        'edited_at',
        'is_deleted',
        'deleted_at',
        'sent_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'emoji_reactions' => 'array',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'edited_at' => 'datetime',
        'deleted_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatbotRoom::class, 'room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_message_id');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
