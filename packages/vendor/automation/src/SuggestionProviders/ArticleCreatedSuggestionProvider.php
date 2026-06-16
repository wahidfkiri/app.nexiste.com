<?php

namespace Vendor\Automation\SuggestionProviders;

use Vendor\Automation\Contracts\SuggestionProvider;
use Vendor\Automation\Data\SuggestionDefinition;
use Vendor\Automation\Services\ExtensionAvailabilityService;

class ArticleCreatedSuggestionProvider implements SuggestionProvider
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

        $suggestions = [];

        $sheetsInstalled = $this->extensions->isActive($tenantId, 'google-sheets');
        $suggestions[] = SuggestionDefinition::make(
            $sheetsInstalled ? 'append_article_sheet_row' : 'install_extension',
            $sheetsInstalled
                ? "Ajouter {$articleName} au catalogue Google Sheets"
                : 'Installer Google Sheets pour journaliser les articles',
            0.88,
            $sheetsInstalled
                ? ['article_id' => $articleId]
                : ['extension_slug' => 'google-sheets', 'article_id' => $articleId, 'target_action' => 'append_article_sheet_row'],
            [
                'integration' => 'google-sheets',
                'installed' => $sheetsInstalled,
                'target_url' => $this->extensions->targetUrl('google-sheets'),
            ]
        );

        $docsInstalled = $this->extensions->isActive($tenantId, 'google-docx');
        $suggestions[] = SuggestionDefinition::make(
            $docsInstalled ? 'create_article_google_doc' : 'install_extension',
            $docsInstalled
                ? "Generer une fiche Google Docs pour {$articleName}"
                : 'Installer Google Docs pour générer une fiche article',
            0.83,
            $docsInstalled
                ? ['article_id' => $articleId]
                : ['extension_slug' => 'google-docx', 'article_id' => $articleId, 'target_action' => 'create_article_google_doc'],
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
                ? 'Créer une note Notion pour cet article'
                : 'Installer Notion Workspace pour documenter cet article',
            0.8,
            $notionInstalled
                ? [
                    'article_id' => $articleId,
                    'supplier_id' => $article['supplier_id'] ?? null,
                    'extension_slug' => 'notion-workspace',
                    'template' => 'article_note',
                    'context_label' => 'Fiche article',
                ]
                : [
                    'extension_slug' => 'notion-workspace',
                    'article_id' => $articleId,
                    'supplier_id' => $article['supplier_id'] ?? null,
                    'target_action' => 'create_notion_page',
                    'template' => 'article_note',
                ],
            [
                'integration' => 'notion-workspace',
                'installed' => $notionInstalled,
                'target_url' => $this->extensions->targetUrl('notion-workspace'),
                'target_blank' => true,
                'template' => 'article_note',
            ]
        );

        return $suggestions;
    }
}
