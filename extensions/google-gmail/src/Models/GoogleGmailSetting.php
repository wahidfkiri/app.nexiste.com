<?php

namespace NexusExtensions\GoogleGmail\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleGmailSetting extends Model
{
    protected $table = 'google_gmail_settings';

    protected $fillable = [
        'tenant_id',
        'signature_enabled',
        'signature_html',
        'signature_text',
        'signature_on_replies',
        'signature_on_forwards',
        'default_cc',
        'default_bcc',
        'polling_interval_seconds',
        'main_labels',
    ];

    protected $casts = [
        'signature_enabled' => 'boolean',
        'signature_on_replies' => 'boolean',
        'signature_on_forwards' => 'boolean',
        'default_cc' => 'array',
        'default_bcc' => 'array',
        'main_labels' => 'array',
        'polling_interval_seconds' => 'integer',
    ];

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}