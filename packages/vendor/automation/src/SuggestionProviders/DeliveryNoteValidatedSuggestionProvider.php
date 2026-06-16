<?php

namespace Vendor\Automation\SuggestionProviders;

use Vendor\Automation\Contracts\SuggestionProvider;
use Vendor\Automation\Data\SuggestionDefinition;
use Vendor\Automation\Services\ExtensionAvailabilityService;

class DeliveryNoteValidatedSuggestionProvider implements SuggestionProvider
{
    public function __construct(
        protected ExtensionAvailabilityService $extensions
    ) {
    }

    public function suggest(string $sourceEvent, array $context = []): iterable
    {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $deliveryNote = (array) ($context['delivery_note'] ?? []);
        $deliveryNoteId = (int) ($deliveryNote['id'] ?? 0);
        $number = (string) ($deliveryNote['number'] ?? 'ce bon de livraison');

        if ($tenantId <= 0 || $deliveryNoteId <= 0) {
            return [];
        }

        $suggestions = [];

        $sheetsInstalled = $this->extensions->isActive($tenantId, 'google-sheets');
        $suggestions[] = SuggestionDefinition::make(
            $sheetsInstalled ? 'append_delivery_note_sheet_row' : 'install_extension',
            $sheetsInstalled
                ? "Ajouter {$number} au registre Google Sheets"
                : 'Installer Google Sheets pour journaliser les bons de livraison',
            0.9,
            $sheetsInstalled
                ? ['delivery_note_id' => $deliveryNoteId]
                : ['extension_slug' => 'google-sheets', 'delivery_note_id' => $deliveryNoteId, 'target_action' => 'append_delivery_note_sheet_row'],
            [
                'integration' => 'google-sheets',
                'installed' => $sheetsInstalled,
                'target_url' => $this->extensions->targetUrl('google-sheets'),
            ]
        );

        $docsInstalled = $this->extensions->isActive($tenantId, 'google-docx');
        $suggestions[] = SuggestionDefinition::make(
            $docsInstalled ? 'create_delivery_note_google_doc' : 'install_extension',
            $docsInstalled
                ? 'Generer un document Google Docs a partir du BL'
                : 'Installer Google Docs pour générer une version éditable du BL',
            0.85,
            $docsInstalled
                ? ['delivery_note_id' => $deliveryNoteId]
                : ['extension_slug' => 'google-docx', 'delivery_note_id' => $deliveryNoteId, 'target_action' => 'create_delivery_note_google_doc'],
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
                ? 'Créer une note Notion de traçabilité logistique'
                : 'Installer Notion Workspace pour documenter le BL et ses écarts',
            0.8,
            $notionInstalled
                ? [
                    'delivery_note_id' => $deliveryNoteId,
                    'client_id' => $deliveryNote['client_id'] ?? null,
                    'extension_slug' => 'notion-workspace',
                    'template' => 'delivery_note_note',
                    'context_label' => 'Trace logistique BL',
                ]
                : [
                    'extension_slug' => 'notion-workspace',
                    'delivery_note_id' => $deliveryNoteId,
                    'client_id' => $deliveryNote['client_id'] ?? null,
                    'target_action' => 'create_notion_page',
                    'template' => 'delivery_note_note',
                ],
            [
                'integration' => 'notion-workspace',
                'installed' => $notionInstalled,
                'target_url' => $this->extensions->targetUrl('notion-workspace'),
                'target_blank' => true,
                'template' => 'delivery_note_note',
            ]
        );

        return $suggestions;
    }
}
