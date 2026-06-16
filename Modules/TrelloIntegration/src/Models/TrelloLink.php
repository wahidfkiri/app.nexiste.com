<?php

namespace Modules\TrelloIntegration\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NexusExtensions\Projects\Models\Project;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class TrelloLink extends Model
{
    use MultiTenantTrait;

    protected $table = 'trello_links';

    protected $fillable = [
        'tenant_id',
        'trello_card_id',
        'project_id',
        'linked_by',
        'notes',
        'linked_at',
    ];

    protected $casts = [
        'linked_at' => 'datetime',
    ];

    public function card(): BelongsTo
    {
        return $this->belongsTo(TrelloCard::class, 'trello_card_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function linkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }
}
