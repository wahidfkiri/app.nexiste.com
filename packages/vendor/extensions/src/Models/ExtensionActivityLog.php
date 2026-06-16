<?php 


namespace Vendor\Extensions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Vendor\CrmCore\Models\Tenant;

class ExtensionActivityLog extends Model
{
    protected $table = 'extension_activity_logs';

    protected $fillable = [
        'extension_id', 'tenant_id', 'user_id',
        'event', 'payload', 'ip_address',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function extension(): BelongsTo
    {
        return $this->belongsTo(Extension::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\Vendor\CrmCore\Models\Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}