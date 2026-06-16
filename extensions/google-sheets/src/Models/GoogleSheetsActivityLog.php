<?php

namespace NexusExtensions\GoogleSheets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class GoogleSheetsActivityLog extends Model
{
    protected $table = 'google_sheets_activity_logs';

    protected $fillable = [
        'tenant_id', 'user_id', 'spreadsheet_id', 'spreadsheet_title',
        'sheet_title', 'action', 'metadata', 'ip_address',
    ];

    protected $casts = ['metadata' => 'array'];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}