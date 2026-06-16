<?php 

namespace Vendor\Extensions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Vendor\CrmCore\Models\Tenant;

class ExtensionReview extends Model
{
    protected $table = 'extension_reviews';

    protected $fillable = [
        'extension_id', 'tenant_id', 'user_id',
        'rating', 'title', 'body',
        'is_verified_purchase', 'is_approved',
    ];

    protected $casts = [
        'is_verified_purchase' => 'boolean',
        'is_approved'          => 'boolean',
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
