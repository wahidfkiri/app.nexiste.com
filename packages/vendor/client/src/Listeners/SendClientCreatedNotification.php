<?php

namespace Vendor\Client\Listeners;

use Illuminate\Support\Facades\Log;
use Vendor\Client\Events\ClientCreated;

class SendClientCreatedNotification
{
    public function handle(ClientCreated $event): void
    {
        Log::info('Client created domain event dispatched.', [
            'tenant_id' => (int) $event->client->tenant_id,
            'client_id' => (int) $event->client->id,
        ]);
    }
}
