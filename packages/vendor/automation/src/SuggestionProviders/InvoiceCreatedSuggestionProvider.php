<?php

namespace Vendor\Automation\SuggestionProviders;

use Vendor\Automation\Contracts\SuggestionProvider;
use Vendor\Automation\Data\SuggestionDefinition;
use Vendor\Automation\Services\ExtensionAvailabilityService;

class InvoiceCreatedSuggestionProvider implements SuggestionProvider
{
    public function __construct(
        protected ExtensionAvailabilityService $extensions
    ) {
    }

    public function suggest(string $sourceEvent, array $context = []): iterable
    {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $invoice = (array) ($context['invoice'] ?? []);
        $invoiceId = (int) ($invoice['id'] ?? 0);

        if ($tenantId <= 0 || $invoiceId <= 0) {
            return [];
        }

        $clientName = (string) ($invoice['client_name'] ?? 'ce client');
        $clientId = (int) ($invoice['client_id'] ?? 0);
        $clientEmail = trim((string) ($invoice['client_email'] ?? ''));
        $status = (string) ($invoice['status'] ?? 'draft');
        $dueDate = $invoice['due_date'] ?? null;

        $suggestions = [];

        if ($status !== 'paid' && $this->canSuggestCustomerEmailFlow($clientId, $clientEmail)) {
            $gmailInstalled = $this->extensions->isActive($tenantId, 'google-gmail');
            $suggestions[] = SuggestionDefinition::make(
                $gmailInstalled ? 'send_invoice_email' : 'install_extension',
                $gmailInstalled
                    ? "Envoyer la facture a {$clientName}"
                    : 'Installer Google Gmail pour envoyer la facture',
                0.94,
                $gmailInstalled
                    ? ['invoice_id' => $invoiceId]
                    : ['extension_slug' => 'google-gmail', 'invoice_id' => $invoiceId, 'target_action' => 'send_invoice_email'],
                [
                    'integration' => 'google-gmail',
                    'installed' => $gmailInstalled,
                    'target_url' => $this->extensions->targetUrl('google-gmail'),
                ]
            );
        }

        if ($dueDate && $status !== 'paid') {
            $calendarInstalled = $this->extensions->isActive($tenantId, 'google-calendar');
            $suggestions[] = SuggestionDefinition::make(
                $calendarInstalled ? 'schedule_invoice_reminder' : 'install_extension',
                $calendarInstalled
                    ? 'Planifier un rappel de paiement'
                    : 'Installer Google Calendar pour planifier un rappel de paiement',
                0.88,
                $calendarInstalled
                    ? ['invoice_id' => $invoiceId, 'due_date' => $dueDate]
                    : ['extension_slug' => 'google-calendar', 'invoice_id' => $invoiceId, 'target_action' => 'schedule_invoice_reminder'],
                [
                    'integration' => 'google-calendar',
                    'installed' => $calendarInstalled,
                    'target_url' => $this->extensions->targetUrl('google-calendar'),
                ]
            );
        }

        $sheetsInstalled = $this->extensions->isActive($tenantId, 'google-sheets');
        $suggestions[] = SuggestionDefinition::make(
            $sheetsInstalled ? 'append_invoice_sheet_row' : 'install_extension',
            $sheetsInstalled
                ? 'Ajouter cette facture dans le suivi Google Sheets'
                : 'Installer Google Sheets pour suivre les factures dans un tableau partagé',
            0.87,
            $sheetsInstalled
                ? ['invoice_id' => $invoiceId]
                : ['extension_slug' => 'google-sheets', 'invoice_id' => $invoiceId, 'target_action' => 'append_invoice_sheet_row'],
            [
                'integration' => 'google-sheets',
                'installed' => $sheetsInstalled,
                'target_url' => $this->extensions->targetUrl('google-sheets'),
            ]
        );

        $docsInstalled = $this->extensions->isActive($tenantId, 'google-docx');
        $suggestions[] = SuggestionDefinition::make(
            $docsInstalled ? 'create_invoice_google_doc' : 'install_extension',
            $docsInstalled
                ? 'Generer une version Word de cette facture dans Google Docs'
                : 'Installer Google Docs pour générer une version éditable de la facture',
            0.83,
            $docsInstalled
                ? ['invoice_id' => $invoiceId]
                : ['extension_slug' => 'google-docx', 'invoice_id' => $invoiceId, 'target_action' => 'create_invoice_google_doc'],
            [
                'integration' => 'google-docx',
                'installed' => $docsInstalled,
                'target_url' => $this->extensions->targetUrl('google-docx'),
            ]
        );

        $projectsInstalled = $this->extensions->isActive($tenantId, 'projects');
        $suggestions[] = SuggestionDefinition::make(
            $projectsInstalled ? 'create_payment_followup_task' : 'install_extension',
            $projectsInstalled
                ? 'Créer une tâche de suivi de paiement'
                : 'Installer Projets pour suivre le paiement dans une tâche',
            0.79,
            $projectsInstalled
                ? ['invoice_id' => $invoiceId]
                : ['extension_slug' => 'projects', 'invoice_id' => $invoiceId, 'target_action' => 'create_payment_followup_task'],
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
                ? 'Créer une page Notion de suivi de facture'
                : 'Installer Notion Workspace pour documenter le suivi de paiement',
            0.8,
            $notionInstalled
                ? [
                    'invoice_id' => $invoiceId,
                    'extension_slug' => 'notion-workspace',
                    'template' => 'invoice_followup',
                    'context_label' => 'Suivi de facture',
                ]
                : [
                    'extension_slug' => 'notion-workspace',
                    'invoice_id' => $invoiceId,
                    'target_action' => 'create_notion_page',
                    'template' => 'invoice_followup',
                ],
            [
                'integration' => 'notion-workspace',
                'installed' => $notionInstalled,
                'target_url' => $this->extensions->targetUrl('notion-workspace'),
                'target_blank' => true,
                'template' => 'invoice_followup',
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
