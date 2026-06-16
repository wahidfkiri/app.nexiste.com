<?php

namespace Vendor\Automation\Listeners;

use Vendor\Automation\Events\AutomationEventQueued;
use Vendor\Automation\Jobs\ExecuteAutomationEventJob;

class QueueAutomationExecution
{
    public function handle(AutomationEventQueued $event): void
    {
        ExecuteAutomationEventJob::dispatch((int) $event->automationEvent->id)
            ->onConnection((string) config('automation.queue.connection', config('queue.default')))
            ->onQueue((string) config('automation.queue.name', 'automation'));
    }
}
