<?php

namespace Vendor\Automation\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Services\AutomationExecutor;

class ExecuteAutomationEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $automationEventId
    ) {
    }

    public function handle(AutomationExecutor $executor): void
    {
        $modelClass = config('automation.models.event', AutomationEvent::class);
        $automationEvent = $modelClass::query()->find($this->automationEventId);

        if (!$automationEvent) {
            return;
        }

        $executor->execute($automationEvent);
    }
}
