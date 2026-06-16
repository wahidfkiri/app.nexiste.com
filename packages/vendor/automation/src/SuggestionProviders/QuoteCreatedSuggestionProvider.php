<?php

namespace Vendor\Automation\SuggestionProviders;

use Vendor\Automation\Contracts\SuggestionProvider;
use Vendor\Automation\Data\SuggestionDefinition;
use Vendor\Automation\Services\ExtensionAvailabilityService;

class QuoteCreatedSuggestionProvider implements SuggestionProvider
{
    public function __construct(
        protected ExtensionAvailabilityService $extensions
    ) {
    }

    public function suggest(string $sourceEvent, array $context = []): iterable
    {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $quote = (array) ($context['quote'] ?? []);
        $quoteId = (int) ($quote['id'] ?? 0);

        if ($tenantId <= 0 || $quoteId <= 0) {
            return [];
        }

        $clientName = (string) ($quote['client_name'] ?? 'ce client');
        $clientId = (int) ($quote['client_id'] ?? 0);
        $clientEmail = trim((string) ($quote['client_email'] ?? ''));
        $validUntil = $quote['valid_until'] ?? null;

        $suggestions = [];

        if ($this->canSuggestCustomerEmailFlow($clientId, $clientEmail)) {
            $gmailInstalled = $this->extensions->isActive($tenantId, 'google-gmail');
            $suggestions[] = SuggestionDefinition::make(
                $gmailInstalled ? 'send_quote_email' : 'install_extension',
                $gmailInstalled
                    ? "Envoyer le devis à {$clientName}"
                    : 'Installer Google Gmail pour envoyer le devis',
                0.93,
                $gmailInstalled
                    ? ['quote_id' => $quoteId]
                    : ['extension_slug' => 'google-gmail', 'quote_id' => $quoteId, 'target_action' => 'send_quote_email'],
                [
                    'integration' => 'google-gmail',
                    'installed' => $gmailInstalled,
                    'target_url' => $this->extensions->targetUrl('google-gmail'),
                ]
            );
        }

        if ($validUntil) {
            $calendarInstalled = $this->extensions->isActive($tenantId, 'google-calendar');
            $suggestions[] = SuggestionDefinition::make(
                $calendarInstalled ? 'schedule_quote_followup' : 'install_extension',
                $calendarInstalled
                    ? 'Planifier une relance commerciale'
                    : 'Installer Google Calendar pour planifier une relance',
                0.86,
                $calendarInstalled
                    ? ['quote_id' => $quoteId, 'valid_until' => $validUntil]
                    : ['extension_slug' => 'google-calendar', 'quote_id' => $quoteId, 'target_action' => 'schedule_quote_followup'],
                [
                    'integration' => 'google-calendar',
                    'installed' => $calendarInstalled,
                    'target_url' => $this->extensions->targetUrl('google-calendar'),
                ]
            );
        }

        $projectsInstalled = $this->extensions->isActive($tenantId, 'projects');
        $suggestions[] = SuggestionDefinition::make(
            $projectsInstalled ? 'create_quote_followup_task' : 'install_extension',
            $projectsInstalled
                ? 'Créer une tâche de suivi du devis'
                : 'Installer Projets pour créer une tâche de suivi du devis',
            0.78,
            $projectsInstalled
                ? ['quote_id' => $quoteId]
                : ['extension_slug' => 'projects', 'quote_id' => $quoteId, 'target_action' => 'create_quote_followup_task'],
            [
                'integration' => 'projects',
                'installed' => $projectsInstalled,
                'target_url' => $this->extensions->targetUrl('projects'),
            ]
        );

        $notionInstalled = $this->extensions->isActive($tenantId, 'notion-workspace');
        $suggestions[] = SuggestionDefinition::make(
            $notionInstalled ? 'create_notion_page' : 'install_extension',
            $notionInstalled
                ? 'Créer une page Notion de suivi du devis'
                : 'Installer Notion Workspace pour documenter le suivi commercial',
            0.8,
            $notionInstalled
                ? [
                    'quote_id' => $quoteId,
                    'extension_slug' => 'notion-workspace',
                    'template' => 'quote_followup',
                    'context_label' => 'Suivi de devis',
                ]
                : [
                    'extension_slug' => 'notion-workspace',
                    'quote_id' => $quoteId,
                    'target_action' => 'create_notion_page',
                    'template' => 'quote_followup',
                ],
            [
                'integration' => 'notion-workspace',
                'installed' => $notionInstalled,
                'target_url' => $this->extensions->targetUrl('notion-workspace'),
                'target_blank' => true,
                'template' => 'quote_followup',
            ]
        );

        return $suggestions;
    }

    protected function canSuggestCustomerEmailFlow(int $clientId, string $clientEmail): bool
    {
        if ($clientId <= 0) {
            return false;
        }

        return filter_var($clientEmail, FILTER_VALIDATE_EMAIL) !== false;
    }
}
