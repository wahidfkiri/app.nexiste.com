<?php

namespace Vendor\Automation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Vendor\Automation\Models\AutomationEvent;

class AutomationEventProcessed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public AutomationEvent $automationEvent
    ) {
    }
}
