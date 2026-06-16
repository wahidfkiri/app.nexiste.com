<?php

namespace Vendor\Automation\SuggestionProviders;

use Vendor\Automation\Contracts\SuggestionProvider;
use Vendor\Automation\Data\SuggestionDefinition;
use Vendor\Automation\Services\ExtensionAvailabilityService;

class SupplierCreatedSuggestionProvider implements SuggestionProvider
{
    public function __construct(
        protected ExtensionAvailabilityService $extensions
    ) {
    }

    public function suggest(string $sourceEvent, array $context = []): iterable
    {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $supplier = (array) ($context['supplier'] ?? []);
        $supplierId = (int) ($supplier['id'] ?? 0);
        $supplierName = (string) ($supplier['name'] ?? 'ce fournisseur');

        if ($tenantId <= 0 || $supplierId <= 0) {
            return [];
        }

        $suggestions = [];

        $sheetsInstalled = $this->extensions->isActive($tenantId, 'google-sheets');
        $suggestions[] = SuggestionDefinition::make(
            $sheetsInstalled ? 'append_supplier_sheet_row' : 'install_extension',
            $sheetsInstalled
                ? "Ajouter {$supplierName} au registre Google Sheets"
                : 'Installer Google Sheets pour centraliser les fournisseurs',
            0.89,
            $sheetsInstalled
                ? ['supplier_id' => $supplierId]
                : ['extension_slug' => 'google-sheets', 'supplier_id' => $supplierId, 'target_action' => 'append_supplier_sheet_row'],
            [
                'integration' => 'google-sheets',
                'installed' => $sheetsInstalled,
                'target_url' => $this->extensions->targetUrl('google-sheets'),
            ]
        );

        $docsInstalled = $this->extensions->isActive($tenantId, 'google-docx');
        $suggestions[] = SuggestionDefinition::make(
            $docsInstalled ? 'create_supplier_google_doc' : 'install_extension',
            $docsInstalled
                ? "Créer une fiche Google Docs pour {$supplierName}"
                : 'Installer Google Docs pour générer une fiche fournisseur éditable',
            0.84,
            $docsInstalled
                ? ['supplier_id' => $supplierId]
                : ['extension_slug' => 'google-docx', 'supplier_id' => $supplierId, 'target_action' => 'create_supplier_google_doc'],
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
                ? 'Créer une page Notion de suivi fournisseur'
                : 'Installer Notion Workspace pour documenter ce fournisseur',
            0.81,
            $notionInstalled
                ? [
                    'supplier_id' => $supplierId,
                    'extension_slug' => 'notion-workspace',
                    'template' => 'supplier_notes',
                    'context_label' => 'Notes fournisseur',
                ]
                : [
                    'extension_slug' => 'notion-workspace',
                    'supplier_id' => $supplierId,
                    'target_action' => 'create_notion_page',
                    'template' => 'supplier_notes',
                ],
            [
                'integration' => 'notion-workspace',
                'installed' => $notionInstalled,
                'target_url' => $this->extensions->targetUrl('notion-workspace'),
                'target_blank' => true,
                'template' => 'supplier_notes',
            ]
        );

        return $suggestions;
    }
}
