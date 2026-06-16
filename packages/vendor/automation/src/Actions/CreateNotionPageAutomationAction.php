<?php

namespace Vendor\Automation\Actions;

use NexusExtensions\NotionWorkspace\Models\NotionPageLink;
use NexusExtensions\NotionWorkspace\Services\NotionWorkspaceApiService;
use NexusExtensions\Projects\Models\Project;
use NexusExtensions\Projects\Models\ProjectTask;
use RuntimeException;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Client\Models\Client;
use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Models\Quote;
use Vendor\Stock\Models\DeliveryNote;
use Vendor\Stock\Models\Order;
use Vendor\Stock\Models\Supplier;

class CreateNotionPageAutomationAction extends AbstractAutomationAction
{
    public function __construct(
        \Vendor\Automation\Services\ExtensionAvailabilityService $extensions,
        protected NotionWorkspaceApiService $notionService
    ) {
        parent::__construct($extensions);
    }

    public function execute(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        return $this->withReconnectHandling('notion-workspace', function () use ($automationEvent, $suggestion) {
            $tenantId = $this->tenantId($automationEvent);
            $this->assertExtensionActive($tenantId, 'notion-workspace', 'Notion Workspace doit être installé pour cette automation.');

            if (!$this->notionService->getToken($tenantId)) {
                throw new RuntimeException("Notion Workspace n'est pas connecté pour ce tenant.");
            }

            $draft = $this->buildDraft($automationEvent, $suggestion);
            $page = $this->notionService->createPage($tenantId, [
                'title' => $draft['title'],
                'content' => $draft['content'],
                'icon' => $draft['icon'],
                'parent_page_id' => $draft['parent_page_id'],
            ]);

            $actor = $this->resolveActorUser($automationEvent);
            $link = NotionPageLink::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'notion_page_id' => (string) $page['id'],
                ],
                [
                    'notion_parent_id' => $page['parent']['page_id'] ?? null,
                    'notion_page_title' => (string) ($page['title'] ?? $draft['title']),
                    'notion_page_url' => (string) ($page['url'] ?? ''),
                    'client_id' => $draft['client_id'],
                    'project_id' => $draft['project_id'],
                    'context_label' => $draft['context_label'],
                    'notes' => $draft['notes'],
                    'linked_by' => (int) $actor->id,
                    'last_synced_at' => now(),
                ]
            );

            return [
                'result' => 'notion_page_created',
                'message' => $draft['success_message'],
                'notion_page_id' => (string) $page['id'],
                'notion_page_title' => (string) ($page['title'] ?? $draft['title']),
                'link_id' => (int) $link->id,
                'client_id' => $draft['client_id'],
                'project_id' => $draft['project_id'],
                'target_url' => (string) ($page['url'] ?? '') ?: $this->routeUrl('notion-workspace.index'),
                'target_blank' => true,
            ];
        });
    }

    protected function buildDraft(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        $payload = $this->payload($automationEvent);
        $meta = $this->meta($suggestion);
        $tenantId = $this->tenantId($automationEvent);

        $template = trim((string) ($payload['template'] ?? $meta['template'] ?? 'generic'));
        $customTitle = $this->sanitizeText((string) ($payload['title'] ?? ''));
        $customContent = trim((string) ($payload['content'] ?? ''));

        if ($customTitle !== '') {
            return [
                'title' => $customTitle,
                'content' => $customContent !== '' ? $customContent : 'Page créée automatiquement depuis une suggestion CRM.',
                'icon' => '',
                'parent_page_id' => $payload['parent_page_id'] ?? null,
                'client_id' => $payload['client_id'] ?? null,
                'project_id' => $payload['project_id'] ?? null,
                'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Documentation')),
                'notes' => $this->sanitizeText((string) ($payload['notes'] ?? "Page Notion créée automatiquement depuis le moteur d'automation.")),
                'success_message' => trim((string) ($payload['success_message'] ?? "Page Notion créée avec succès.")),
            ];
        }

        return match ($template) {
            'article_note' => $this->articleDraft($tenantId, $payload, $suggestion),
            'client_notes' => $this->clientDraft($tenantId, $payload, $suggestion),
            'project_brief' => $this->projectDraft($tenantId, $payload, $suggestion),
            'task_spec' => $this->taskDraft($tenantId, $payload, $suggestion),
            'quote_followup' => $this->quoteDraft($tenantId, $payload, $suggestion),
            'invoice_followup' => $this->invoiceDraft($tenantId, $payload, $suggestion),
            'supplier_notes' => $this->supplierDraft($tenantId, $payload, $suggestion),
            'stock_order_note' => $this->stockOrderDraft($tenantId, $payload, $suggestion),
            'delivery_note_note' => $this->deliveryNoteDraft($tenantId, $payload, $suggestion),
            'low_stock_note' => $this->lowStockDraft($tenantId, $payload, $suggestion),
            default => [
                'title' => 'Page Notion CRM',
                'content' => 'Page créée automatiquement depuis une suggestion CRM.',
                'icon' => '',
                'parent_page_id' => $payload['parent_page_id'] ?? null,
                'client_id' => $payload['client_id'] ?? null,
                'project_id' => $payload['project_id'] ?? null,
                'context_label' => 'Documentation',
                'notes' => "Page Notion créée automatiquement depuis le moteur d'automation.",
                'success_message' => "Page Notion créée avec succès.",
            ],
        };
    }

    protected function articleDraft(int $tenantId, array $payload, ?AutomationSuggestion $suggestion): array
    {
        $articleId = $this->modelId($payload, $suggestion, 'article_id', \Vendor\Stock\Models\Article::class);
        if (!$articleId) {
            throw new RuntimeException('Article introuvable pour la création de la page Notion.');
        }

        $article = $this->loadArticle($tenantId, $articleId);

        return [
            'title' => 'Article - ' . $this->sanitizeText((string) $article->name) . ' - Fiche',
            'content' => implode(PHP_EOL, array_filter([
                'Type de page: Fiche article',
                'Article: ' . $this->sanitizeText((string) $article->name),
                $article->sku ? 'SKU: ' . $this->sanitizeText((string) $article->sku) : null,
                $article->unit ? 'Unite: ' . $this->sanitizeText((string) $article->unit) : null,
                'Stock courant: ' . number_format((float) $article->current_stock, 4, ',', ' '),
                'Seuil mini: ' . number_format((float) $article->min_stock, 4, ',', ' '),
                $article->supplier ? 'Fournisseur: ' . $this->sanitizeText((string) $article->supplier->name) : null,
                '',
                'A documenter:',
                '- Positionnement produit',
                '- Cycle d approvisionnement',
                '- Variantes ou references voisines',
                '- Contraintes logistiques',
                '- Risques de rupture',
            ])),
            'icon' => '',
            'parent_page_id' => $payload['parent_page_id'] ?? null,
            'client_id' => null,
            'project_id' => $payload['project_id'] ?? null,
            'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Fiche article')),
            'notes' => 'Page Notion créée automatiquement pour documenter un article.',
            'success_message' => 'Page Notion article créée avec succès.',
        ];
    }

    protected function clientDraft(int $tenantId, array $payload, ?AutomationSuggestion $suggestion): array
    {
        $clientId = $this->modelId($payload, $suggestion, 'client_id', Client::class);
        if (!$clientId) {
            throw new RuntimeException('Client introuvable pour la création de la page Notion.');
        }

        $client = $this->loadClient($tenantId, $clientId);
        $clientName = $this->clientDisplayName($client);

        return [
            'title' => 'Client - ' . $clientName . ' - Notes internes',
            'content' => implode(PHP_EOL, array_filter([
                'Type de page: Notes client',
                'Client: ' . $clientName,
                $client->email ? 'Email: ' . $client->email : null,
                $client->phone ? 'Telephone: ' . $client->phone : null,
                $client->website ? 'Site web: ' . $client->website : null,
                '',
                'A documenter:',
                '- Contexte commercial',
                '- Besoins et priorites',
                '- Risques et objections',
                '- Prochaines actions',
            ])),
            'icon' => '',
            'parent_page_id' => $payload['parent_page_id'] ?? null,
            'client_id' => (int) $client->id,
            'project_id' => null,
            'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Notes client')),
            'notes' => 'Page Notion créée automatiquement après création du client.',
            'success_message' => 'Page Notion client créée avec succès.',
        ];
    }

    protected function projectDraft(int $tenantId, array $payload, ?AutomationSuggestion $suggestion): array
    {
        $projectId = $this->modelId($payload, $suggestion, 'project_id', Project::class);
        if (!$projectId) {
            throw new RuntimeException('Projet introuvable pour la création de la page Notion.');
        }

        $project = $this->loadProject($tenantId, $projectId);
        $clientName = $project->client ? $this->clientDisplayName($project->client) : null;

        return [
            'title' => 'Projet - ' . $this->sanitizeText((string) $project->name) . ' - Brief',
            'content' => implode(PHP_EOL, array_filter([
                'Type de page: Brief projet',
                'Projet: ' . $this->sanitizeText((string) $project->name),
                $clientName ? 'Client: ' . $clientName : null,
                $project->status ? 'Statut: ' . $this->sanitizeText((string) $project->status) : null,
                $project->description ? 'Description: ' . $this->sanitizeText((string) $project->description) : null,
                '',
                'A documenter:',
                '- Objectifs du projet',
                '- Parties prenantes',
                '- Livrables attendus',
                '- Risques et dependances',
                "- Plan d'execution",
            ])),
            'icon' => '',
            'parent_page_id' => $payload['parent_page_id'] ?? null,
            'client_id' => $project->client_id ? (int) $project->client_id : null,
            'project_id' => (int) $project->id,
            'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Brief projet')),
            'notes' => 'Page Notion créée automatiquement après création du projet.',
            'success_message' => 'Page Notion projet créée avec succès.',
        ];
    }

    protected function taskDraft(int $tenantId, array $payload, ?AutomationSuggestion $suggestion): array
    {
        $taskId = $this->modelId($payload, $suggestion, 'task_id', ProjectTask::class);
        if (!$taskId) {
            throw new RuntimeException('Tâche introuvable pour la création de la page Notion.');
        }

        $task = $this->loadProjectTask($tenantId, $taskId);
        $project = $task->project;

        return [
            'title' => 'Tâche - ' . $this->sanitizeText((string) $task->title) . ' - Spec',
            'content' => implode(PHP_EOL, array_filter([
                'Type de page: Spécification de tâche',
                'Tâche: ' . $this->sanitizeText((string) $task->title),
                $project ? 'Projet: ' . $this->sanitizeText((string) $project->name) : null,
                $task->due_date ? 'Echeance: ' . $task->due_date : null,
                $task->description ? 'Description: ' . $this->sanitizeText((string) $task->description) : null,
                '',
                'A documenter:',
                '- Contexte',
                "- Etapes d'execution",
                '- Definition of done',
                '- Points de validation',
            ])),
            'icon' => '',
            'parent_page_id' => $payload['parent_page_id'] ?? null,
            'client_id' => $project?->client_id ? (int) $project->client_id : null,
            'project_id' => $project?->id ? (int) $project->id : null,
            'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Spécification de tâche')),
            'notes' => 'Page Notion créée automatiquement pour documenter une tâche projet.',
            'success_message' => 'Page Notion tâche créée avec succès.',
        ];
    }

    protected function quoteDraft(int $tenantId, array $payload, ?AutomationSuggestion $suggestion): array
    {
        $quoteId = $this->modelId($payload, $suggestion, 'quote_id', Quote::class);
        if (!$quoteId) {
            throw new RuntimeException('Devis introuvable pour la création de la page Notion.');
        }

        $quote = $this->loadQuote($tenantId, $quoteId);
        $quoteRef = $this->sanitizeText((string) ($quote->quote_number ?: $quote->number ?: ('Devis #' . $quote->id)));
        $clientName = $quote->client ? $this->clientDisplayName($quote->client) : 'client';

        return [
            'title' => $quoteRef . ' - Suivi commercial',
            'content' => implode(PHP_EOL, array_filter([
                'Type de page: Suivi de devis',
                'Reference: ' . $quoteRef,
                'Client: ' . $clientName,
                $quote->valid_until ? "Valide jusqu'au: " . $quote->valid_until : null,
                '',
                'A documenter:',
                '- Historique des echanges',
                '- Objections du client',
                '- Conditions negociees',
                '- Prochaine relance',
            ])),
            'icon' => '',
            'parent_page_id' => $payload['parent_page_id'] ?? null,
            'client_id' => $quote->client_id ? (int) $quote->client_id : null,
            'project_id' => $payload['project_id'] ?? null,
            'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Suivi de devis')),
            'notes' => 'Page Notion créée automatiquement pour suivre un devis.',
            'success_message' => 'Page Notion devis créée avec succès.',
        ];
    }

    protected function invoiceDraft(int $tenantId, array $payload, ?AutomationSuggestion $suggestion): array
    {
        $invoiceId = $this->modelId($payload, $suggestion, 'invoice_id', Invoice::class);
        if (!$invoiceId) {
            throw new RuntimeException('Facture introuvable pour la création de la page Notion.');
        }

        $invoice = $this->loadInvoice($tenantId, $invoiceId);
        $invoiceRef = $this->sanitizeText((string) ($invoice->invoice_number ?: $invoice->number ?: ('Facture #' . $invoice->id)));
        $clientName = $invoice->client ? $this->clientDisplayName($invoice->client) : 'client';

        return [
            'title' => $invoiceRef . ' - Suivi paiement',
            'content' => implode(PHP_EOL, array_filter([
                'Type de page: Suivi de facture',
                'Reference: ' . $invoiceRef,
                'Client: ' . $clientName,
                $invoice->status ? 'Statut: ' . $this->sanitizeText((string) $invoice->status) : null,
                $invoice->due_date ? "Date d'echeance: " . $invoice->due_date : null,
                '',
                'A documenter:',
                '- Etat du paiement',
                '- Relances effectuees',
                '- Blocages signales',
                '- Prochaine action',
            ])),
            'icon' => '',
            'parent_page_id' => $payload['parent_page_id'] ?? null,
            'client_id' => $invoice->client_id ? (int) $invoice->client_id : null,
            'project_id' => $payload['project_id'] ?? null,
            'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Suivi de facture')),
            'notes' => 'Page Notion créée automatiquement pour suivre une facture.',
            'success_message' => 'Page Notion facture créée avec succès.',
        ];
    }

    protected function stockOrderDraft(int $tenantId, array $payload, ?AutomationSuggestion $suggestion): array
    {
        $orderId = $this->modelId($payload, $suggestion, 'stock_order_id', Order::class);
        if (!$orderId) {
            throw new RuntimeException('Commande fournisseur introuvable pour la création de la page Notion.');
        }

        $order = $this->loadStockOrder($tenantId, $orderId);
        $supplierName = $this->sanitizeText((string) optional($order->supplier)->name);

        return [
            'title' => 'Commande fournisseur - ' . $this->sanitizeText((string) $order->number) . ' - Suivi',
            'content' => implode(PHP_EOL, array_filter([
                'Type de page: Suivi de commande fournisseur',
                'Commande: ' . $this->sanitizeText((string) $order->number),
                $supplierName !== '' ? 'Fournisseur: ' . $supplierName : null,
                $order->status ? 'Statut: ' . $this->sanitizeText((string) $order->status) : null,
                $order->expected_date ? "Date attendue: " . $order->expected_date->format('d/m/Y') : null,
                $order->reference ? 'Reference: ' . $this->sanitizeText((string) $order->reference) : null,
                'Nombre de lignes: ' . (string) $order->items->count(),
                '',
                'A documenter:',
                '- Confirmation fournisseur',
                '- Delais et risques',
                '- Ecarts quantites / prix',
                '- Actions de reception',
                '',
                'Checklist de suivi:',
                '- Accusé de réception confirmé',
                '- Date de livraison verifiee',
                '- Conditions d achat archivees',
            ])),
            'icon' => '',
            'parent_page_id' => $payload['parent_page_id'] ?? null,
            'client_id' => null,
            'project_id' => $payload['project_id'] ?? null,
            'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Suivi commande fournisseur')),
            'notes' => 'Page Notion créée automatiquement pour documenter une commande fournisseur.',
            'success_message' => 'Page Notion commande fournisseur créée avec succès.',
        ];
    }

    protected function deliveryNoteDraft(int $tenantId, array $payload, ?AutomationSuggestion $suggestion): array
    {
        $deliveryNoteId = $this->modelId($payload, $suggestion, 'delivery_note_id', DeliveryNote::class);
        if (!$deliveryNoteId) {
            throw new RuntimeException('Bon de livraison introuvable pour la création de la page Notion.');
        }

        $deliveryNote = $this->loadDeliveryNote($tenantId, $deliveryNoteId);
        $counterparty = $deliveryNote->type === 'in'
            ? $this->sanitizeText((string) optional($deliveryNote->supplier)->name)
            : $this->sanitizeText((string) optional($deliveryNote->client)->company_name);

        return [
            'title' => 'BL - ' . $this->sanitizeText((string) $deliveryNote->number) . ' - Trace logistique',
            'content' => implode(PHP_EOL, array_filter([
                'Type de page: Trace logistique',
                'Bon de livraison: ' . $this->sanitizeText((string) $deliveryNote->number),
                'Type: ' . ($deliveryNote->type === 'in' ? 'Entree' : 'Sortie'),
                $counterparty !== '' ? 'Tiers: ' . $counterparty : null,
                $deliveryNote->reference ? 'Reference: ' . $this->sanitizeText((string) $deliveryNote->reference) : null,
                $deliveryNote->order ? 'Commande liée: ' . $this->sanitizeText((string) $deliveryNote->order->number) : null,
                'Nombre de lignes: ' . (string) $deliveryNote->items->count(),
                '',
                'A documenter:',
                '- Conformite des quantites',
                '- Incident ou reserve',
                '- Documents transmis',
                '- Action suivante',
                '',
                'Points de controle:',
                '- Signature ou preuve de remise',
                '- Ecarts identifies',
                '- Correctif logistique lance si necessaire',
            ])),
            'icon' => '',
            'parent_page_id' => $payload['parent_page_id'] ?? null,
            'client_id' => $deliveryNote->client_id ? (int) $deliveryNote->client_id : null,
            'project_id' => $payload['project_id'] ?? null,
            'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Trace logistique BL')),
            'notes' => 'Page Notion créée automatiquement pour tracer un bon de livraison.',
            'success_message' => 'Page Notion bon de livraison créée avec succès.',
        ];
    }

    protected function supplierDraft(int $tenantId, array $payload, ?AutomationSuggestion $suggestion): array
    {
        $supplierId = $this->modelId($payload, $suggestion, 'supplier_id', Supplier::class);
        if (!$supplierId) {
            throw new RuntimeException('Fournisseur introuvable pour la création de la page Notion.');
        }

        $supplier = $this->loadSupplier($tenantId, $supplierId);

        return [
            'title' => 'Fournisseur - ' . $this->sanitizeText((string) $supplier->name) . ' - Notes',
            'content' => implode(PHP_EOL, array_filter([
                'Type de page: Notes fournisseur',
                'Fournisseur: ' . $this->sanitizeText((string) $supplier->name),
                $supplier->contact_name ? 'Contact principal: ' . $this->sanitizeText((string) $supplier->contact_name) : null,
                $supplier->email ? 'Email: ' . $this->sanitizeText((string) $supplier->email) : null,
                $supplier->phone ? 'Telephone: ' . $this->sanitizeText((string) $supplier->phone) : null,
                $supplier->city ? 'Ville: ' . $this->sanitizeText((string) $supplier->city) : null,
                $supplier->country ? 'Pays: ' . $this->sanitizeText((string) $supplier->country) : null,
                '',
                'A documenter:',
                '- Conditions tarifaires',
                '- Delais habituels',
                '- Niveau de fiabilite',
                '- Points de contact utiles',
                '- Historique des incidents',
            ])),
            'icon' => '',
            'parent_page_id' => $payload['parent_page_id'] ?? null,
            'client_id' => null,
            'project_id' => $payload['project_id'] ?? null,
            'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Notes fournisseur')),
            'notes' => 'Page Notion créée automatiquement pour documenter un fournisseur.',
            'success_message' => 'Page Notion fournisseur créée avec succès.',
        ];
    }

    protected function lowStockDraft(int $tenantId, array $payload, ?AutomationSuggestion $suggestion): array
    {
        $articleId = $this->modelId($payload, $suggestion, 'article_id', \Vendor\Stock\Models\Article::class);
        if (!$articleId) {
            throw new RuntimeException('Article introuvable pour la création de la page Notion.');
        }

        $article = $this->loadArticle($tenantId, $articleId);

        return [
            'title' => 'Alerte stock - ' . $this->sanitizeText((string) $article->name),
            'content' => implode(PHP_EOL, array_filter([
                'Type de page: Alerte stock',
                'Article: ' . $this->sanitizeText((string) $article->name),
                $article->sku ? 'SKU: ' . $this->sanitizeText((string) $article->sku) : null,
                'Stock courant: ' . number_format((float) $article->current_stock, 4, ',', ' ') . ' ' . (string) ($article->unit ?? ''),
                'Seuil mini: ' . number_format((float) $article->min_stock, 4, ',', ' ') . ' ' . (string) ($article->unit ?? ''),
                $article->supplier ? 'Fournisseur: ' . $this->sanitizeText((string) $article->supplier->name) : null,
                '',
                'A documenter:',
                '- Cause probable de la baisse',
                '- Niveau d urgence',
                '- Decision de reapprovisionnement',
                '- Responsable du suivi',
                '- Date de resolution attendue',
            ])),
            'icon' => '',
            'parent_page_id' => $payload['parent_page_id'] ?? null,
            'client_id' => null,
            'project_id' => $payload['project_id'] ?? null,
            'context_label' => $this->sanitizeText((string) ($payload['context_label'] ?? 'Alerte stock')),
            'notes' => "Page Notion créée automatiquement après détection d'un stock bas.",
            'success_message' => 'Page Notion alerte stock créée avec succès.',
        ];
    }
}
