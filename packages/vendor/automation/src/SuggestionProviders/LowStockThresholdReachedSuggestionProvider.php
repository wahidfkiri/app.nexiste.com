<?php

namespace Vendor\Automation\SuggestionProviders;

use Vendor\Automation\Contracts\SuggestionProvider;
use Vendor\Automation\Data\SuggestionDefinition;
use Vendor\Automation\Services\ExtensionAvailabilityService;

class LowStockThresholdReachedSuggestionProvider implements SuggestionProvider
{
    public function __construct(
        protected ExtensionAvailabilityService $extensions
    ) {
    }

    public function suggest(string $sourceEvent, array $context = []): iterable
    {
        $tenantId = (int) ($context['tenant_id'] ?? 0);
        $article = (array) ($context['article'] ?? []);
        $articleId = (int) ($article['id'] ?? 0);
        $articleName = (string) ($article['name'] ?? 'cet article');

        if ($tenantId <= 0 || $articleId <= 0) {
            return [];
        }

        $dedupeStem = 'low-stock:' . $tenantId . ':' . $articleId;
        $suggestions = [];

        $sheetsInstalled = $this->extensions->isActive($tenantId, 'google-sheets');
        $suggestions[] = SuggestionDefinition::make(
            $sheetsInstalled ? 'append_low_stock_sheet_row' : 'install_extension',
            $sheetsInstalled
                ? "Journaliser l'alerte stock de {$articleName} dans Google Sheets"
                : 'Installer Google Sheets pour suivre les alertes stock',
            0.9,
            $sheetsInstalled
                ? ['article_id' => $articleId]
                : ['extension_slug' => 'google-sheets', 'article_id' => $articleId, 'target_action' => 'append_low_stock_sheet_row'],
            [
                'integration' => 'google-sheets',
                'installed' => $sheetsInstalled,
                'target_url' => $this->extensions->targetUrl('google-sheets'),
            ]
        )->withDedupeKey($dedupeStem . ':sheets');

        $docsInstalled = $this->extensions->isActive($tenantId, 'google-docx');
        $suggestions[] = SuggestionDefinition::make(
            $docsInstalled ? 'create_low_stock_google_doc' : 'install_extension',
            $docsInstalled
                ? "Generer une note Google Docs pour l'alerte stock de {$articleName}"
                : 'Installer Google Docs pour formaliser une alerte stock',
            0.82,
            $docsInstalled
                ? ['article_id' => $articleId]
                : ['extension_slug' => 'google-docx', 'article_id' => $articleId, 'target_action' => 'create_low_stock_google_doc'],
            [
                'integration' => 'google-docx',
                'installed' => $docsInstalled,
                'target_url' => $this->extensions->targetUrl('google-docx'),
            ]
        )->withDedupeKey($dedupeStem . ':docs');

        $notionInstalled = $this->extensions->isActive($tenantId, 'notion-workspace');
        $suggestions[] = SuggestionDefinition::make(
            $notionInstalled ? 'create_notion_page' : 'install_extension',
            $notionInstalled
                ? 'Créer une page Notion de suivi stock bas'
                : 'Installer Notion Workspace pour documenter cette alerte stock',
            0.84,
            $notionInstalled
                ? [
                    'article_id' => $articleId,
                    'supplier_id' => $article['supplier_id'] ?? null,
                    'extension_slug' => 'notion-workspace',
                    'template' => 'low_stock_note',
                    'context_label' => 'Alerte stock',
                ]
                : [
                    'extension_slug' => 'notion-workspace',
                    'article_id' => $articleId,
                    'supplier_id' => $article['supplier_id'] ?? null,
                    'target_action' => 'create_notion_page',
                    'template' => 'low_stock_note',
                ],
            [
                'integration' => 'notion-workspace',
                'installed' => $notionInstalled,
                'target_url' => $this->extensions->targetUrl('notion-workspace'),
                'target_blank' => true,
                'template' => 'low_stock_note',
            ]
        )->withDedupeKey($dedupeStem . ':notion');

        return $suggestions;
    }
}
