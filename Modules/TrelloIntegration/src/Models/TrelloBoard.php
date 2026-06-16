<?php

namespace Modules\TrelloIntegration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class TrelloBoard extends Model
{
    use MultiTenantTrait;

    protected $table = 'trello_boards';

    protected $fillable = [
        'tenant_id',
        'trello_id',
        'name',
        'description',
        'url',
        'workspace_id',
        'background_color',
        'background_image_url',
        'closed',
        'starred',
        'last_activity_at',
        'last_synced_at',
        'raw_payload',
    ];

    protected $casts = [
        'closed' => 'boolean',
        'starred' => 'boolean',
        'last_activity_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function lists(): HasMany
    {
        return $this->hasMany(TrelloList::class)->orderBy('position');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(TrelloCard::class)->orderBy('position');
    }
}
