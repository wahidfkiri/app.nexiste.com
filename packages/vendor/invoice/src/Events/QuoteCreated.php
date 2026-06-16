<?php

namespace Vendor\Invoice\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\Automation\Contracts\AutomationContextEvent;
use Vendor\Invoice\Models\Quote;

class QuoteCreated implements AutomationContextEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Quote $quote,
        public array $meta = []
    ) {
    }

    public function automationSourceEvent(): string
    {
        return 'quote_created';
    }

    public function automationTenantId(): int
    {
        return (int) $this->quote->tenant_id;
    }

    public function automationUserId(): ?int
    {
        return $this->quote->user_id ? (int) $this->quote->user_id : null;
    }

    public function automationSourceType(): ?string
    {
        return $this->quote::class;
    }

    public function automationSourceId(): int|string|null
    {
        return $this->quote->getKey();
    }

    public function automationSource(): mixed
    {
        return $this->quote;
    }

    public function automationContext(): array
    {
        return [
            'quote' => [
                'id' => (int) $this->quote->id,
                'number' => (string) $this->quote->number,
                'status' => (string) $this->quote->status,
                'client_id' => $this->quote->client_id ? (int) $this->quote->client_id : null,
                'client_name' => (string) optional($this->quote->client)->company_name,
                'client_email' => (string) optional($this->quote->client)->email,
                'currency' => (string) $this->quote->currency,
                'total' => (float) $this->quote->total,
                'valid_until' => optional($this->quote->valid_until)?->toDateString(),
            ],
            'meta' => $this->meta,
        ];
    }
}

