<?php

namespace NexusExtensions\GoogleSheets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vendor\CrmCore\Models\Tenant;

class GoogleSheetsSheet extends Model
{
    protected $table = 'google_sheets_sheets';

    protected $fillable = [
        'tenant_id', 'spreadsheet_local_id', 'spreadsheet_id',
        'sheet_id', 'title', 'index', 'sheet_type',
        'row_count', 'column_count', 'hidden',
    ];

    protected $casts = [
        'sheet_id'     => 'integer',
        'index'        => 'integer',
        'row_count'    => 'integer',
        'column_count' => 'integer',
        'hidden'       => 'boolean',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }

    public function spreadsheet(): BelongsTo
    {
        return $this->belongsTo(GoogleSheetsSpreadsheet::class, 'spreadsheet_local_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}