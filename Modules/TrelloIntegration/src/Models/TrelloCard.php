<?php

namespace Modules\TrelloIntegration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class TrelloCard extends Model
{
    use MultiTenantTrait;

    protected $table = 'trello_cards';

    protected $fillable = [
        'tenant_id',
        'trello_board_id',
        'trello_list_id',
        'trello_id',
        'name',
        'description',
        'url',
        'short_url',
        'position',
        'due_at',
        'last_activity_at',
        'closed',
        'labels',
        'members',
        'badges',
        'cover_color',
        'cover_image_url',
        'last_synced_at',
        'raw_payload',
    ];

    protected $casts = [
        'position' => 'decimal:4',
        'due_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'closed' => 'boolean',
        'labels' => 'array',
        'members' => 'array',
        'badges' => 'array',
        'last_synced_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(TrelloBoard::class, 'trello_board_id');
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(TrelloList::class, 'trello_list_id');
    }

    public function link(): HasOne
    {
        return $this->hasOne(TrelloLink::class, 'trello_card_id');
    }
}
