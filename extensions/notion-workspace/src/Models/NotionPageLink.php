<?php

namespace NexusExtensions\NotionWorkspace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\Client\Models\Client;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class NotionPageLink extends Model
{
    use MultiTenantTrait;

    protected $table = 'notion_page_links';

    protected $fillable = [
        'tenant_id',
        'notion_page_id',
        'notion_parent_id',
        'notion_page_title',
        'notion_page_url',
        'client_id',
        'project_id',
        'context_label',
        'notes',
        'linked_by',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(\NexusExtensions\Projects\Models\Project::class, 'project_id');
    }

    public function linkedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'linked_by');
    }
}