<?php

namespace Vendor\Stock\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\Automation\Contracts\AutomationContextEvent;
use Vendor\Stock\Models\Order;

class StockOrderCreated implements AutomationContextEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Order $order,
        public array $meta = []
    ) {
    }

    public function automationSourceEvent(): string
    {
        return 'stock_order_created';
    }

    public function automationTenantId(): int
    {
        return (int) $this->order->tenant_id;
    }

    public function automationUserId(): ?int
    {
        return $this->order->user_id ? (int) $this->order->user_id : null;
    }

    public function automationSourceType(): ?string
    {
        return $this->order::class;
    }

    public function automationSourceId(): int|string|null
    {
        return $this->order->getKey();
    }

    public function automationSource(): mixed
    {
        return $this->order;
    }

    public function automationContext(): array
    {
        $this->order->loadMissing(['supplier', 'items.article']);

        return [
            'stock_order' => [
                'id' => (int) $this->order->id,
                'number' => (string) $this->order->number,
                'reference' => (string) ($this->order->reference ?? ''),
                'status' => (string) ($this->order->status ?? ''),
                'supplier_id' => $this->order->supplier_id ? (int) $this->order->supplier_id : null,
                'supplier_name' => (string) optional($this->order->supplier)->name,
                'supplier_email' => (string) optional($this->order->supplier)->email,
                'order_date' => optional($this->order->order_date)?->toDateString(),
                'expected_date' => optional($this->order->expected_date)?->toDateString(),
                'total' => (float) ($this->order->total ?? 0),
                'items_count' => (int) $this->order->items->count(),
            ],
            'meta' => $this->meta,
        ];
    }
}
