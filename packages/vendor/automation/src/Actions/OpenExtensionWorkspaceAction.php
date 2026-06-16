<?php

namespace Vendor\Automation\Actions;

use RuntimeException;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;

class OpenExtensionWorkspaceAction extends AbstractAutomationAction
{
    public function execute(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        $payload = $this->payload($automationEvent);
        $meta = $this->meta($suggestion);

        $extensionSlug = trim((string) ($payload['extension_slug'] ?? $meta['integration'] ?? ''));
        $targetUrl = trim((string) ($payload['target_url'] ?? $meta['target_url'] ?? ''));

        if ($targetUrl === '' && $extensionSlug !== '') {
            $targetUrl = $this->extensions->targetUrl($extensionSlug);
        }

        if ($targetUrl === '') {
            throw new RuntimeException(__('automation::automation.actions.workspace_target_missing'));
        }

        return [
            'result' => 'workspace_ready',
            'message' => trim((string) ($payload['message'] ?? __('automation::automation.actions.workspace_shortcut_saved'))),
            'extension_slug' => $extensionSlug !== '' ? $extensionSlug : null,
            'target_url' => $targetUrl,
            'target_blank' => $this->shouldOpenInNewTab($extensionSlug, $payload, $meta),
        ];
    }

    protected function shouldOpenInNewTab(string $extensionSlug, array $payload, array $meta): bool
    {
        if (array_key_exists('target_blank', $payload)) {
            return (bool) $payload['target_blank'];
        }

        if (array_key_exists('target_blank', $meta)) {
            return (bool) $meta['target_blank'];
        }

        return in_array($extensionSlug, ['notion-workspace', 'google-calendar'], true);
    }
}
