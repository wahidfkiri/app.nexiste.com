<?php

namespace Vendor\Automation\SuggestionProviders;

use Vendor\Automation\Contracts\SuggestionProvider;
use Vendor\Automation\Data\SuggestionDefinition;
use Vendor\Automation\Services\ExtensionAvailabilityService;

class StockOrderCreatedSuggestionProvider implements SuggestionProvider
{
    public function __construct(
        protected ExtensionAvailabilityService $extensions
    ) {
    }

    public function suggest(string $sourceEvent, array $context = []): iterable
    {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $order = (array) ($context['stock_order'] ?? []);
        $orderId = (int) ($order['id'] ?? 0);
        $orderNumber = (string) ($order['number'] ?? 'cette commande fournisseur');

        if ($tenantId <= 0 || $orderId <= 0) {
            return [];
        }

        $suggestions = [];

        $sheetsInstalled = $this->extensions->isActive($tenantId, 'google-sheets');
        $suggestions[] = SuggestionDefinition::make(
            $sheetsInstalled ? 'append_stock_order_sheet_row' : 'install_extension',
            $sheetsInstalled
                ? "Ajouter {$orderNumber} au suivi Google Sheets"
                : 'Installer Google Sheets pour suivre les commandes fournisseurs',
            0.91,
            $sheetsInstalled
                ? ['stock_order_id' => $orderId]
                : ['extension_slug' => 'google-sheets', 'stock_order_id' => $orderId, 'target_action' => 'append_stock_order_sheet_row'],
            [
                'integration' => 'google-sheets',
                'installed' => $sheetsInstalled,
                'target_url' => $this->extensions->targetUrl('google-sheets'),
            ]
        );

        $docsInstalled = $this->extensions->isActive($tenantId, 'google-docx');
        $suggestions[] = SuggestionDefinition::make(
            $docsInstalled ? 'create_stock_order_google_doc' : 'install_extension',
            $docsInstalled
                ? "Generer un document Google Docs pour {$orderNumber}"
                : 'Installer Google Docs pour générer un document de commande fournisseur',
            0.86,
            $docsInstalled
                ? ['stock_order_id' => $orderId]
                : ['extension_slug' => 'google-docx', 'stock_order_id' => $orderId, 'target_action' => 'create_stock_order_google_doc'],
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
                ? 'Créer une note Notion de suivi fournisseur'
                : 'Installer Notion Workspace pour documenter les commandes fournisseurs',
            0.82,
            $notionInstalled
                ? [
                    'stock_order_id' => $orderId,
                    'extension_slug' => 'notion-workspace',
                    'template' => 'stock_order_note',
                    'context_label' => 'Suivi commande fournisseur',
                ]
                : [
                    'extension_slug' => 'notion-workspace',
                    'stock_order_id' => $orderId,
                    'target_action' => 'create_notion_page',
                    'template' => 'stock_order_note',
                ],
            [
                'integration' => 'notion-workspace',
                'installed' => $notionInstalled,
                'target_url' => $this->extensions->targetUrl('notion-workspace'),
                'target_blank' => true,
                'template' => 'stock_order_note',
            ]
        );

        return $suggestions;
    }
}
