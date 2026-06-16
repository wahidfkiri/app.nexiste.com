<?php

namespace NexusExtensions\NotionWorkspace\Models;

use Illuminate\Database\Eloquent\Model;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class NotionPageShare extends Model
{
    use MultiTenantTrait;

    protected $table = 'notion_page_shares';

    protected $fillable = [
        'tenant_id',
        'notion_page_id',
        'user_id',
        'can_edit',
        'can_comment',
        'can_share',
        'shared_by',
    ];

    protected $casts = [
        'can_edit' => 'boolean',
        'can_comment' => 'boolean',
        'can_share' => 'boolean',
    ];

    public function page()
    {
        return $this->belongsTo(NotionPage::class, 'notion_page_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function sharedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'shared_by');
    }
}

