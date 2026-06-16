<?php

namespace NexusExtensions\GoogleSheets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vendor\CrmCore\Models\Tenant;

class GoogleSheetsSpreadsheet extends Model
{
    use SoftDeletes;

    protected $table = 'google_sheets_spreadsheets';

    protected $fillable = [
        'tenant_id', 'spreadsheet_id', 'title', 'locale', 'timezone',
        'spreadsheet_url', 'is_shared',
        'created_by', 'modified_by',
        'drive_created_at', 'drive_modified_at',
    ];

    protected $casts = [
        'is_shared'         => 'boolean',
        'drive_created_at'  => 'datetime',
        'drive_modified_at' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function modifiedBy(): BelongsTo { return $this->belongsTo(User::class, 'modified_by'); }

    public function sheets(): HasMany
    {
        return $this->hasMany(GoogleSheetsSheet::class, 'spreadsheet_local_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}