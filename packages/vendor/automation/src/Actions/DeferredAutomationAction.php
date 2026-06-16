<?php

namespace Vendor\Automation\Actions;

use Vendor\Automation\Contracts\AutomationAction;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;

class DeferredAutomationAction implements AutomationAction
{
    public function execute(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        return [
            'result' => 'recorded',
            'message' => __('automation::automation.actions.deferred_action_saved'),
            'action_type' => (string) $automationEvent->action_type,
            'suggestion_id' => $suggestion?->id,
            'target_url' => $suggestion?->meta['target_url'] ?? null,
            'target_blank' => (bool) ($suggestion?->meta['target_blank'] ?? false),
        ];
    }
}
