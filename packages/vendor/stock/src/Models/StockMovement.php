<?php

namespace Vendor\Stock\Models;

use Illuminate\Database\Eloquent\Model;
use Vendor\CrmCore\Traits\MultiTenantTrait;

class StockMovement extends Model
{
    use MultiTenantTrait;

    protected $table = 'stock_movements';

    protected $appends = [
        'direction_label',
        'movement_type_label',
        'display_reference',
        'display_reason',
        'happened_at_display',
    ];

    protected $fillable = [
        'tenant_id', 'user_id', 'article_id', 'delivery_note_id', 'delivery_note_item_id',
        'source_type', 'source_id', 'movement_type', 'direction', 'quantity', 'unit',
        'reference', 'reason', 'happened_at', 'notes', 'meta',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'happened_at' => 'datetime',
        'meta' => 'array',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function deliveryNote()
    {
        return $this->belongsTo(DeliveryNote::class, 'delivery_note_id');
    }

    public function deliveryNoteItem()
    {
        return $this->belongsTo(DeliveryNoteItem::class, 'delivery_note_item_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function getSignedQuantityAttribute(): float
    {
        $quantity = (float) $this->quantity;
        return $this->direction === 'out' ? -1 * $quantity : $quantity;
    }

    public function getDirectionLabelAttribute(): string
    {
        return $this->direction === 'out'
            ? trans('stock::stock.common.direction_out')
            : trans('stock::stock.common.direction_in');
    }

    public function getMovementTypeLabelAttribute(): string
    {
        $labels = (array) config('stock.movement_types', []);
        return (string) ($labels[$this->movement_type] ?? $this->movement_type);
    }

    public function getDisplayReferenceAttribute(): string
    {
        return match ((string) $this->reference) {
            'LEGACY-STOCK' => trans('stock::stock.reasons.opening_stock_legacy'),
            'OPENING-STOCK' => trans('stock::stock.common.opening_stock'),
            default => (string) ($this->reference ?: trans('stock::stock.common.none_short')),
        };
    }

    public function getDisplayReasonAttribute(): string
    {
        $reason = (string) ($this->reason ?? '');

        if ($this->movement_type === 'opening_balance' && $this->reference === 'LEGACY-STOCK') {
            return trans('stock::stock.reasons.opening_stock_legacy');
        }

        if (str_starts_with($reason, 'Posted from ')) {
            return trans('stock::stock.reasons.posted_from_note', [
                'type' => mb_substr($reason, strlen('Posted from ')),
            ]);
        }

        if (str_starts_with($reason, 'Reversal after cancellation of ')) {
            return trans('stock::stock.reasons.reversal_after_cancellation', [
                'type' => mb_substr($reason, strlen('Reversal after cancellation of ')),
            ]);
        }

        return match ($reason) {
            'Opening stock declared at article creation' => trans('stock::stock.reasons.opening_stock_declared'),
            default => $reason !== '' ? $reason : trans('stock::stock.common.none_short'),
        };
    }

    public function getHappenedAtDisplayAttribute(): string
    {
        return $this->happened_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? trans('stock::stock.common.none_short');
    }
}
