<?php

namespace Vendor\Automation\Listeners;

use Illuminate\Support\Facades\Log;
use Throwable;
use Vendor\Automation\Contracts\AutomationContextEvent;
use Vendor\Automation\Services\AutomationEngine;

class CaptureAutomationSuggestions
{
    public function __construct(
        protected AutomationEngine $engine
    ) {
    }

    public function handle(AutomationContextEvent $event): void
    {
        try {
            $context = array_merge($event->automationContext(), [
                'tenant_id' => $event->automationTenantId(),
                'user_id' => $event->automationUserId(),
                'source_event' => $event->automationSourceEvent(),
                'source_type' => $event->automationSourceType(),
                'source_id' => $event->automationSourceId(),
                'source' => $event->automationSource(),
            ]);

            $this->engine->capture(
                $event->automationSourceEvent(),
                $context,
                $event->automationTenantId(),
                $event->automationUserId(),
                $event->automationSourceType(),
                $event->automationSourceId()
            );
        } catch (Throwable $e) {
            Log::warning('Automation suggestion capture failed', [
                'event' => $event->automationSourceEvent(),
                'tenant_id' => $event->automationTenantId(),
                'source_type' => $event->automationSourceType(),
                'source_id' => $event->automationSourceId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
