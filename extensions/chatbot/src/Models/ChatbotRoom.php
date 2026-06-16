<?php

namespace NexusExtensions\Chatbot\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vendor\CrmCore\Models\Tenant;

class ChatbotRoom extends Model
{
    protected $table = 'chatbot_rooms';

    protected $fillable = [
        'tenant_id',
        'created_by',
        'updated_by',
        'room_uuid',
        'name',
        'description',
        'icon',
        'color',
        'is_private',
        'is_archived',
        'is_default',
        'messages_count',
        'last_message_at',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_archived' => 'boolean',
        'is_default' => 'boolean',
        'messages_count' => 'integer',
        'last_message_at' => 'datetime',
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

    public function members(): HasMany
    {
        return $this->hasMany(ChatbotRoomMember::class, 'room_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'room_id');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
