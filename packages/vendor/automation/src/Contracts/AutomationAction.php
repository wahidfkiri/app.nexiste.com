<?php

namespace Vendor\Automation\Contracts;

use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;

interface AutomationAction
{
    public function execute(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array;
}
