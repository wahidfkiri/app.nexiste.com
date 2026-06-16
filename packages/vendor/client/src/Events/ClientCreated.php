<?php

namespace Vendor\Client\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\Automation\Contracts\AutomationContextEvent;
use Vendor\Client\Models\Client;

class ClientCreated implements AutomationContextEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Client $client,
        public array $meta = []
    ) {
    }

    public function automationSourceEvent(): string
    {
        return 'client_created';
    }

    public function automationTenantId(): int
    {
        return (int) $this->client->tenant_id;
    }

    public function automationUserId(): ?int
    {
        return $this->client->user_id ? (int) $this->client->user_id : null;
    }

    public function automationSourceType(): ?string
    {
        return $this->client::class;
    }

    public function automationSourceId(): int|string|null
    {
        return $this->client->getKey();
    }

    public function automationSource(): mixed
    {
        return $this->client;
    }

    public function automationContext(): array
    {
        return [
            'client' => [
                'id' => (int) $this->client->id,
                'company_name' => (string) $this->client->company_name,
                'contact_name' => (string) ($this->client->contact_name ?? ''),
                'email' => (string) ($this->client->email ?? ''),
                'phone' => (string) ($this->client->phone ?? ''),
                'status' => (string) ($this->client->status ?? ''),
                'type' => (string) ($this->client->type ?? ''),
                'assigned_to' => $this->client->assigned_to ? (int) $this->client->assigned_to : null,
            ],
            'meta' => $this->meta,
        ];
    }
}
