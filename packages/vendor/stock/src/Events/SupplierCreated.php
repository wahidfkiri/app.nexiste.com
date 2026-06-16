<?php

namespace Vendor\Stock\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\Automation\Contracts\AutomationContextEvent;
use Vendor\Stock\Models\Supplier;

class SupplierCreated implements AutomationContextEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Supplier $supplier,
        public array $meta = []
    ) {
    }

    public function automationSourceEvent(): string
    {
        return 'supplier_created';
    }

    public function automationTenantId(): int
    {
        return (int) $this->supplier->tenant_id;
    }

    public function automationUserId(): ?int
    {
        return $this->supplier->user_id ? (int) $this->supplier->user_id : null;
    }

    public function automationSourceType(): ?string
    {
        return $this->supplier::class;
    }

    public function automationSourceId(): int|string|null
    {
        return $this->supplier->getKey();
    }

    public function automationSource(): mixed
    {
        return $this->supplier;
    }

    public function automationContext(): array
    {
        return [
            'supplier' => [
                'id' => (int) $this->supplier->id,
                'name' => (string) $this->supplier->name,
                'email' => (string) ($this->supplier->email ?? ''),
                'phone' => (string) ($this->supplier->phone ?? ''),
                'contact_name' => (string) ($this->supplier->contact_name ?? ''),
                'city' => (string) ($this->supplier->city ?? ''),
                'country' => (string) ($this->supplier->country ?? ''),
            ],
            'meta' => $this->meta,
        ];
    }
}
