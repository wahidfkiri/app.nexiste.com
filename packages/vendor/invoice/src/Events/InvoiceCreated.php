<?php

namespace Vendor\Invoice\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\Automation\Contracts\AutomationContextEvent;
use Vendor\Invoice\Models\Invoice;

class InvoiceCreated implements AutomationContextEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public array $meta = []
    ) {
    }

    public function automationSourceEvent(): string
    {
        return 'invoice_created';
    }

    public function automationTenantId(): int
    {
        return (int) $this->invoice->tenant_id;
    }

    public function automationUserId(): ?int
    {
        return $this->invoice->user_id ? (int) $this->invoice->user_id : null;
    }

    public function automationSourceType(): ?string
    {
        return $this->invoice::class;
    }

    public function automationSourceId(): int|string|null
    {
        return $this->invoice->getKey();
    }

    public function automationSource(): mixed
    {
        return $this->invoice;
    }

    public function automationContext(): array
    {
        return [
            'invoice' => [
                'id' => (int) $this->invoice->id,
                'number' => (string) $this->invoice->number,
                'status' => (string) $this->invoice->status,
                'client_id' => $this->invoice->client_id ? (int) $this->invoice->client_id : null,
                'client_name' => (string) optional($this->invoice->client)->company_name,
                'client_email' => (string) optional($this->invoice->client)->email,
                'currency' => (string) $this->invoice->currency,
                'total' => (float) $this->invoice->total,
                'due_date' => optional($this->invoice->due_date)?->toDateString(),
            ],
            'meta' => $this->meta,
        ];
    }
}

