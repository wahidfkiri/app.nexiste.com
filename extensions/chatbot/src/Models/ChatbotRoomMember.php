<?php

namespace NexusExtensions\Chatbot\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class ChatbotRoomMember extends Model
{
    protected $table = 'chatbot_room_members';

    protected $fillable = [
        'tenant_id',
        'room_id',
        'user_id',
        'role',
        'is_muted',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'is_muted' => 'boolean',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
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

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
