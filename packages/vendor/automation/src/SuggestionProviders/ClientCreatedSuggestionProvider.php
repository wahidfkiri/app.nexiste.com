<?php

namespace Vendor\Automation\SuggestionProviders;

use Illuminate\Support\Facades\Route;
use Vendor\Automation\Contracts\SuggestionProvider;
use Vendor\Automation\Data\SuggestionDefinition;
use Vendor\Automation\Services\ExtensionAvailabilityService;

class ClientCreatedSuggestionProvider implements SuggestionProvider
{
    public function __construct(
        protected ExtensionAvailabilityService $extensions
    ) {
    }

    public function suggest(string $sourceEvent, array $context = []): iterable
    {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $client = (array) ($context['client'] ?? []);
        $clientId = (int) ($client['id'] ?? 0);
        $clientName = (string) ($client['company_name'] ?? $client['contact_name'] ?? 'ce client');

        if ($tenantId <= 0 || $clientId <= 0) {
            return [];
        }

        $suggestions = [];

        $calendarInstalled = $this->extensions->isActive($tenantId, 'google-calendar');
        $suggestions[] = SuggestionDefinition::make(
            $calendarInstalled ? 'create_followup_meeting' : 'install_extension',
            $calendarInstalled
                ? "Planifier un rendez-vous interne de découverte pour {$clientName}"
                : 'Installer Google Calendar pour planifier un rendez-vous',
            0.89,
            $calendarInstalled
                ? ['client_id' => $clientId, 'meeting_type' => 'discovery']
                : ['extension_slug' => 'google-calendar', 'client_id' => $clientId, 'target_action' => 'create_followup_meeting'],
            [
                'integration' => 'google-calendar',
                'installed' => $calendarInstalled,
                'target_url' => $this->extensions->targetUrl('google-calendar'),
            ]
        );

        $gmailInstalled = $this->extensions->isActive($tenantId, 'google-gmail');
        $suggestions[] = SuggestionDefinition::make(
            $gmailInstalled ? 'send_followup_meeting_email' : 'install_extension',
            $gmailInstalled
                ? "Envoyer un email à {$clientName} pour proposer un rendez-vous"
                : 'Installer Google Gmail pour proposer un rendez-vous par email',
            0.86,
            $gmailInstalled
                ? ['client_id' => $clientId]
                : ['extension_slug' => 'google-gmail', 'client_id' => $clientId, 'target_action' => 'send_followup_meeting_email'],
            [
                'integration' => 'google-gmail',
                'installed' => $gmailInstalled,
                'target_url' => $this->extensions->targetUrl('google-gmail'),
            ]
        );

        $invoiceInstalled = $this->extensions->isActive($tenantId, 'invoice');
        $quoteUrl = Route::has('invoices.quotes.create')
            ? route('invoices.quotes.create') . '?client_id=' . $clientId
            : $this->extensions->targetUrl('invoice');

        $suggestions[] = SuggestionDefinition::make(
            $invoiceInstalled ? 'create_quote' : 'install_extension',
            $invoiceInstalled
                ? "Créer un devis pour {$clientName}"
                : 'Installer la facturation pour créer un devis',
            0.84,
            $invoiceInstalled
                ? ['client_id' => $clientId]
                : ['extension_slug' => 'invoice', 'client_id' => $clientId, 'target_action' => 'create_quote'],
            [
                'integration' => 'invoice',
                'installed' => $invoiceInstalled,
                'target_url' => $quoteUrl,
            ]
        );

        $sheetsInstalled = $this->extensions->isActive($tenantId, 'google-sheets');
        $suggestions[] = SuggestionDefinition::make(
            $sheetsInstalled ? 'append_client_sheet_row' : 'install_extension',
            $sheetsInstalled
                ? 'Ajouter ce client dans le registre Google Sheets'
                : 'Installer Google Sheets pour enregistrer ce client dans un tableau partagé',
            0.83,
            $sheetsInstalled
                ? ['client_id' => $clientId]
                : ['extension_slug' => 'google-sheets', 'client_id' => $clientId, 'target_action' => 'append_client_sheet_row'],
            [
                'integration' => 'google-sheets',
                'installed' => $sheetsInstalled,
                'target_url' => $this->extensions->targetUrl('google-sheets'),
            ]
        );

        $docsInstalled = $this->extensions->isActive($tenantId, 'google-docx');
        $suggestions[] = SuggestionDefinition::make(
            $docsInstalled ? 'create_client_google_doc' : 'install_extension',
            $docsInstalled
                ? "Créer une fiche client Google Docs pour {$clientName}"
                : 'Installer Google Docs pour générer une fiche client éditable',
            0.78,
            $docsInstalled
                ? ['client_id' => $clientId]
                : ['extension_slug' => 'google-docx', 'client_id' => $clientId, 'target_action' => 'create_client_google_doc'],
            [
                'integration' => 'google-docx',
                'installed' => $docsInstalled,
                'target_url' => $this->extensions->targetUrl('google-docx'),
            ]
        );

        $notionInstalled = $this->extensions->isActive($tenantId, 'notion-workspace');
        $suggestions[] = SuggestionDefinition::make(
            $notionInstalled ? 'create_notion_page' : 'install_extension',
            $notionInstalled
                ? "Créer une page Notion de notes pour {$clientName}"
                : 'Installer Notion Workspace pour centraliser les notes client',
            0.82,
            $notionInstalled
                ? [
                    'client_id' => $clientId,
                    'extension_slug' => 'notion-workspace',
                    'template' => 'client_notes',
                    'context_label' => 'Notes client',
                ]
                : [
                    'extension_slug' => 'notion-workspace',
                    'client_id' => $clientId,
                    'target_action' => 'create_notion_page',
                    'template' => 'client_notes',
                ],
            [
                'integration' => 'notion-workspace',
                'installed' => $notionInstalled,
                'target_url' => $this->extensions->targetUrl('notion-workspace'),
                'target_blank' => true,
                'template' => 'client_notes',
            ]
        );

        return $suggestions;
    }
}
