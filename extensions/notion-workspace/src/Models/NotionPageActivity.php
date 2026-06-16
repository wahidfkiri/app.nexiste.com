<?php

namespace NexusExtensions\NotionWorkspace\Models;

use Illuminate\Database\Eloquent\Model;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class NotionPageActivity extends Model
{
    use MultiTenantTrait;

    protected $table = 'notion_page_activities';

    protected $fillable = [
        'tenant_id',
        'notion_page_id',
        'user_id',
        'event',
        'description',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function page()
    {
        return $this->belongsTo(NotionPage::class, 'notion_page_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}

