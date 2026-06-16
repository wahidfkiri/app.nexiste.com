<?php

namespace Modules\TrelloIntegration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class TrelloList extends Model
{
    use MultiTenantTrait;

    protected $table = 'trello_lists';

    protected $fillable = [
        'tenant_id',
        'trello_board_id',
        'trello_id',
        'name',
        'position',
        'closed',
        'last_synced_at',
        'raw_payload',
    ];

    protected $casts = [
        'position' => 'decimal:4',
        'closed' => 'boolean',
        'last_synced_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function board(): BelongsTo
    {
        return $this->belongsTo(TrelloBoard::class, 'trello_board_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(TrelloCard::class)->orderBy('position');
    }
}
