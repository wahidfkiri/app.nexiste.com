<?php

namespace Vendor\Automation\Listeners;

use Vendor\Automation\Events\AutomationEventFailed;
use Vendor\Automation\Services\AutomationReconnectNotificationService;

class SyncReconnectNotificationOnFailure
{
    public function __construct(
        protected AutomationReconnectNotificationService $notifications
    ) {
    }

    public function handle(AutomationEventFailed $event): void
    {
        $suggestion = $event->automationEvent->suggestion;

        if (!$suggestion && $event->automationEvent->triggered_by_suggestion_id) {
            $suggestion = $event->automationEvent->suggestion()->first();
        }

        $this->notifications->syncForSuggestion($suggestion);
    }
}
