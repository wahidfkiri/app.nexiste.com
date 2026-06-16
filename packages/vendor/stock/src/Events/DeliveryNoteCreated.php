<?php

namespace Vendor\Stock\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\Automation\Contracts\AutomationContextEvent;
use Vendor\Stock\Models\DeliveryNote;

class DeliveryNoteCreated implements AutomationContextEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public DeliveryNote $deliveryNote,
        public array $meta = []
    ) {
    }

    public function automationSourceEvent(): string
    {
        return 'delivery_note_created';
    }

    public function automationTenantId(): int
    {
        return (int) $this->deliveryNote->tenant_id;
    }

    public function automationUserId(): ?int
    {
        return $this->deliveryNote->user_id ? (int) $this->deliveryNote->user_id : null;
    }

    public function automationSourceType(): ?string
    {
        return $this->deliveryNote::class;
    }

    public function automationSourceId(): int|string|null
    {
        return $this->deliveryNote->getKey();
    }

    public function automationSource(): mixed
    {
        return $this->deliveryNote;
    }

    public function automationContext(): array
    {
        $this->deliveryNote->loadMissing(['supplier', 'client', 'order', 'invoice', 'items.article']);

        return [
            'delivery_note' => [
                'id' => (int) $this->deliveryNote->id,
                'number' => (string) $this->deliveryNote->number,
                'reference' => (string) ($this->deliveryNote->reference ?? ''),
                'type' => (string) ($this->deliveryNote->type ?? ''),
                'status' => (string) ($this->deliveryNote->status ?? ''),
                'supplier_id' => $this->deliveryNote->supplier_id ? (int) $this->deliveryNote->supplier_id : null,
                'supplier_name' => (string) optional($this->deliveryNote->supplier)->name,
                'client_id' => $this->deliveryNote->client_id ? (int) $this->deliveryNote->client_id : null,
                'client_name' => (string) optional($this->deliveryNote->client)->company_name,
                'stock_order_id' => $this->deliveryNote->stock_order_id ? (int) $this->deliveryNote->stock_order_id : null,
                'stock_order_number' => (string) optional($this->deliveryNote->order)->number,
                'invoice_id' => $this->deliveryNote->invoice_id ? (int) $this->deliveryNote->invoice_id : null,
                'invoice_number' => (string) optional($this->deliveryNote->invoice)->number,
                'issue_date' => optional($this->deliveryNote->issue_date)?->toDateString(),
                'items_count' => (int) $this->deliveryNote->items->count(),
            ],
            'meta' => $this->meta,
        ];
    }
}
