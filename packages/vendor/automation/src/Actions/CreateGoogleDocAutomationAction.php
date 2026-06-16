<?php

namespace Vendor\Automation\Actions;

use NexusExtensions\GoogleDocx\Services\GoogleDocxService;
use RuntimeException;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\Stock\Models\Article;
use Vendor\Client\Models\Client;
use Vendor\Invoice\Models\Invoice;
use Vendor\Stock\Models\DeliveryNote;
use Vendor\Stock\Models\Order;
use Vendor\Stock\Models\Supplier;

class CreateGoogleDocAutomationAction extends AbstractAutomationAction
{
    public function __construct(
        \Vendor\Automation\Services\ExtensionAvailabilityService $extensions,
        protected GoogleDocxService $docxService
    ) {
        parent::__construct($extensions);
    }

    public function execute(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        return match ((string) $automationEvent->action_type) {
            'create_article_google_doc' => $this->createArticleDocument($automationEvent, $suggestion),
            'create_client_google_doc' => $this->createClientDocument($automationEvent, $suggestion),
            'create_invoice_google_doc' => $this->createInvoiceDocument($automationEvent, $suggestion),
            'create_supplier_google_doc' => $this->createSupplierDocument($automationEvent, $suggestion),
            'create_stock_order_google_doc' => $this->createStockOrderDocument($automationEvent, $suggestion),
            'create_delivery_note_google_doc' => $this->createDeliveryNoteDocument($automationEvent, $suggestion),
            'create_low_stock_google_doc' => $this->createLowStockDocument($automationEvent, $suggestion),
            default => throw new RuntimeException('Type de document Google Docs non pris en charge.'),
        };
    }

    protected function createArticleDocument(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-docx', 'Google Docs doit être installé pour générer ce document.');

        return $this->withReconnectHandling('google-docx', function () use ($automationEvent, $suggestion, $tenantId) {
            $articleId = $this->modelId($this->payload($automationEvent), $suggestion, 'article_id', Article::class);
            if (!$articleId) {
                throw new RuntimeException('Article introuvable pour la création du document.');
            }

            $article = $this->loadArticle($tenantId, $articleId);
            $title = 'Fiche article - ' . (string) $article->name;
            $content = implode("\n", array_filter([
                'Fiche article generee automatiquement depuis le module stock.',
                '',
                'Article: ' . (string) $article->name,
                'SKU: ' . (string) ($article->sku ?? ''),
                'Unite: ' . (string) ($article->unit ?? ''),
                'Prix achat: ' . number_format((float) ($article->purchase_price ?? 0), 4, ',', ' '),
                'Prix vente: ' . number_format((float) ($article->sale_price ?? 0), 4, ',', ' '),
                'Stock courant: ' . number_format((float) $article->current_stock, 4, ',', ' '),
                'Seuil mini: ' . number_format((float) $article->min_stock, 4, ',', ' '),
                'Fournisseur: ' . (string) optional($article->supplier)->name,
                'Statut: ' . (string) ($article->status ?? ''),
                $this->sourceUrlForModel($article) ? 'Lien CRM: ' . $this->sourceUrlForModel($article) : null,
                '',
                'A documenter:',
                '- Positionnement produit',
                '- Conditions d achat',
                '- Usages et variantes',
                '- Risques de rupture',
                '- Informations logistiques',
            ]));

            $document = $this->docxService->createDocument($tenantId, $title, $content);

            return [
                'result' => 'document_created',
                'message' => 'Document Google Docs créé pour cet article.',
                'document_id' => $document['document_id'] ?? null,
                'target_url' => $document['document_url'] ?? $this->routeUrl('google-docx.index'),
                'target_blank' => true,
            ];
        });
    }

    protected function createClientDocument(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-docx', 'Google Docs doit être installé pour générer ce document.');

        return $this->withReconnectHandling('google-docx', function () use ($automationEvent, $suggestion, $tenantId) {
            $clientId = $this->modelId($this->payload($automationEvent), $suggestion, 'client_id', Client::class);
            if (!$clientId) {
                throw new RuntimeException('Client introuvable pour la création du document.');
            }

            $client = $this->loadClient($tenantId, $clientId);
            $title = 'Fiche client - ' . $this->clientDisplayName($client);
            $content = implode("\n", array_filter([
                'Fiche client generee automatiquement depuis le CRM.',
                '',
                'Societe: ' . (string) $client->company_name,
                'Contact: ' . (string) ($client->contact_name ?? ''),
                'Email: ' . (string) ($client->email ?? ''),
                'Telephone: ' . (string) ($client->phone ?? ''),
                'Statut: ' . (string) ($client->status ?? ''),
                'Type: ' . (string) ($client->type ?? ''),
                $this->sourceUrlForModel($client) ? 'Lien CRM: ' . $this->sourceUrlForModel($client) : null,
                '',
                'Notes',
                '-----',
                (string) ($client->notes ?? ''),
            ]));

            $document = $this->docxService->createDocument($tenantId, $title, $content);

            return [
                'result' => 'document_created',
                'message' => 'Document Google Docs créé pour ce client.',
                'document_id' => $document['document_id'] ?? null,
                'target_url' => $document['document_url'] ?? $this->routeUrl('google-docx.index'),
                'target_blank' => true,
            ];
        });
    }

    protected function createInvoiceDocument(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-docx', 'Google Docs doit être installé pour générer ce document.');

        return $this->withReconnectHandling('google-docx', function () use ($automationEvent, $suggestion, $tenantId) {
            $invoiceId = $this->modelId($this->payload($automationEvent), $suggestion, 'invoice_id', Invoice::class);
            if (!$invoiceId) {
                throw new RuntimeException('Facture introuvable pour la création du document.');
            }

            $invoice = $this->loadInvoice($tenantId, $invoiceId);
            $title = 'Version Word - Facture ' . (string) $invoice->number;
            $content = implode("\n", array_filter([
                'Document de facture genere automatiquement depuis le CRM.',
                '',
                'Facture: ' . (string) $invoice->number,
                'Client: ' . (string) optional($invoice->client)->company_name,
                'Email client: ' . (string) optional($invoice->client)->email,
                'Statut: ' . (string) $invoice->status,
                'Date d emission: ' . (string) ($invoice->issue_date?->format('d/m/Y') ?? ''),
                'Echeance: ' . (string) ($invoice->due_date?->format('d/m/Y') ?? ''),
                'Montant total: ' . $this->formatMoney((float) $invoice->total, (string) $invoice->currency),
                'Montant restant: ' . $this->formatMoney((float) $invoice->amount_due, (string) $invoice->currency),
                $this->sourceUrlForModel($invoice) ? 'Lien CRM: ' . $this->sourceUrlForModel($invoice) : null,
                '',
                'Notes',
                '-----',
                (string) ($invoice->notes ?? ''),
            ]));

            $document = $this->docxService->createDocument($tenantId, $title, $content);

            return [
                'result' => 'document_created',
                'message' => 'Document Google Docs créé pour cette facture.',
                'document_id' => $document['document_id'] ?? null,
                'target_url' => $document['document_url'] ?? $this->routeUrl('google-docx.index'),
                'target_blank' => true,
            ];
        });
    }

    protected function createSupplierDocument(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-docx', 'Google Docs doit être installé pour générer ce document.');

        return $this->withReconnectHandling('google-docx', function () use ($automationEvent, $suggestion, $tenantId) {
            $supplierId = $this->modelId($this->payload($automationEvent), $suggestion, 'supplier_id', Supplier::class);
            if (!$supplierId) {
                throw new RuntimeException('Fournisseur introuvable pour la création du document.');
            }

            $supplier = $this->loadSupplier($tenantId, $supplierId);
            $title = 'Fiche fournisseur - ' . (string) $supplier->name;
            $content = implode("\n", array_filter([
                'Fiche fournisseur generee automatiquement depuis le module stock.',
                '',
                'Fournisseur: ' . (string) $supplier->name,
                'Contact principal: ' . (string) ($supplier->contact_name ?? ''),
                'Email: ' . (string) ($supplier->email ?? ''),
                'Telephone: ' . (string) ($supplier->phone ?? ''),
                'Ville: ' . (string) ($supplier->city ?? ''),
                'Pays: ' . (string) ($supplier->country ?? ''),
                'Articles references: ' . (string) $supplier->articles->count(),
                'Commandes fournisseurs: ' . (string) $supplier->orders->count(),
                $this->sourceUrlForModel($supplier) ? 'Lien CRM: ' . $this->sourceUrlForModel($supplier) : null,
                '',
                'Points a documenter',
                '------------------',
                '- Conditions d achat',
                '- Delais moyens',
                '- Contacts de secours',
                '- Risques et dependances',
                '',
                'Notes',
                '-----',
                (string) ($supplier->notes ?? ''),
            ]));

            $document = $this->docxService->createDocument($tenantId, $title, $content);

            return [
                'result' => 'document_created',
                'message' => 'Document Google Docs créé pour ce fournisseur.',
                'document_id' => $document['document_id'] ?? null,
                'target_url' => $document['document_url'] ?? $this->routeUrl('google-docx.index'),
                'target_blank' => true,
            ];
        });
    }

    protected function createStockOrderDocument(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-docx', 'Google Docs doit être installé pour générer ce document.');

        return $this->withReconnectHandling('google-docx', function () use ($automationEvent, $suggestion, $tenantId) {
            $orderId = $this->modelId($this->payload($automationEvent), $suggestion, 'stock_order_id', Order::class);
            if (!$orderId) {
                throw new RuntimeException('Commande fournisseur introuvable pour la création du document.');
            }

            $order = $this->loadStockOrder($tenantId, $orderId);
            $lines = $order->items->map(function ($item) {
                return sprintf(
                    '- %s | Qte: %s %s | PU: %s | Total: %s',
                    (string) $item->name,
                    (string) $item->quantity,
                    (string) $item->unit,
                    (string) $item->unit_price,
                    (string) $item->total
                );
            })->implode("\n");

            $title = 'Commande fournisseur - ' . (string) $order->number;
            $content = implode("\n", array_filter([
                'Synthese de commande fournisseur generee automatiquement.',
                '',
                'Commande: ' . (string) $order->number,
                'Fournisseur: ' . (string) optional($order->supplier)->name,
                'Statut: ' . (string) $order->status,
                'Date commande: ' . (string) ($order->order_date?->format('d/m/Y') ?? ''),
                'Date attendue: ' . (string) ($order->expected_date?->format('d/m/Y') ?? ''),
                'Reference: ' . (string) ($order->reference ?? ''),
                'Total: ' . number_format((float) $order->total, 2, ',', ' '),
                $this->sourceUrlForModel($order) ? 'Lien CRM: ' . $this->sourceUrlForModel($order) : null,
                '',
                'Lignes',
                '------',
                $lines,
                '',
                'Notes',
                '-----',
                (string) ($order->notes ?? ''),
            ]));

            $document = $this->docxService->createDocument($tenantId, $title, $content);

            return [
                'result' => 'document_created',
                'message' => 'Document Google Docs créé pour cette commande fournisseur.',
                'document_id' => $document['document_id'] ?? null,
                'target_url' => $document['document_url'] ?? $this->routeUrl('google-docx.index'),
                'target_blank' => true,
            ];
        });
    }

    protected function createDeliveryNoteDocument(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-docx', 'Google Docs doit être installé pour générer ce document.');

        return $this->withReconnectHandling('google-docx', function () use ($automationEvent, $suggestion, $tenantId) {
            $deliveryNoteId = $this->modelId($this->payload($automationEvent), $suggestion, 'delivery_note_id', DeliveryNote::class);
            if (!$deliveryNoteId) {
                throw new RuntimeException('Bon de livraison introuvable pour la création du document.');
            }

            $deliveryNote = $this->loadDeliveryNote($tenantId, $deliveryNoteId);
            $lines = $deliveryNote->items->map(function ($item) {
                return sprintf(
                    '- %s | SKU: %s | Qte: %s %s',
                    (string) $item->name,
                    (string) ($item->sku ?: $item->article?->sku ?: ''),
                    (string) $item->quantity,
                    (string) $item->unit
                );
            })->implode("\n");

            $counterparty = $deliveryNote->type === 'in'
                ? (string) optional($deliveryNote->supplier)->name
                : (string) optional($deliveryNote->client)->company_name;

            $title = 'Bon de livraison - ' . (string) $deliveryNote->number;
            $content = implode("\n", array_filter([
                'Document BL genere automatiquement depuis le CRM.',
                '',
                'Numero: ' . (string) $deliveryNote->number,
                'Type: ' . (string) $deliveryNote->type,
                'Statut: ' . (string) $deliveryNote->status,
                'Date: ' . (string) ($deliveryNote->issue_date?->format('d/m/Y') ?? ''),
                'Tiers: ' . $counterparty,
                'Commande liée: ' . (string) optional($deliveryNote->order)->number,
                'Facture liée: ' . (string) optional($deliveryNote->invoice)->number,
                $this->sourceUrlForModel($deliveryNote) ? 'Lien CRM: ' . $this->sourceUrlForModel($deliveryNote) : null,
                '',
                'Lignes',
                '------',
                $lines,
                '',
                'Notes',
                '-----',
                (string) ($deliveryNote->notes ?? ''),
            ]));

            $document = $this->docxService->createDocument($tenantId, $title, $content);

            return [
                'result' => 'document_created',
                'message' => 'Document Google Docs créé pour ce bon de livraison.',
                'document_id' => $document['document_id'] ?? null,
                'target_url' => $document['document_url'] ?? $this->routeUrl('google-docx.index'),
                'target_blank' => true,
            ];
        });
    }

    protected function createLowStockDocument(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-docx', 'Google Docs doit être installé pour générer ce document.');

        return $this->withReconnectHandling('google-docx', function () use ($automationEvent, $suggestion, $tenantId) {
            $articleId = $this->modelId($this->payload($automationEvent), $suggestion, 'article_id', Article::class);
            if (!$articleId) {
                throw new RuntimeException('Article introuvable pour la création du document.');
            }

            $article = $this->loadArticle($tenantId, $articleId);
            $title = 'Alerte stock - ' . (string) $article->name;
            $content = implode("\n", array_filter([
                'Alerte stock faible generee automatiquement depuis le CRM.',
                '',
                'Article: ' . (string) $article->name,
                'SKU: ' . (string) ($article->sku ?? ''),
                'Stock courant: ' . number_format((float) $article->current_stock, 4, ',', ' ') . ' ' . (string) ($article->unit ?? ''),
                'Seuil mini: ' . number_format((float) $article->min_stock, 4, ',', ' ') . ' ' . (string) ($article->unit ?? ''),
                'Fournisseur: ' . (string) optional($article->supplier)->name,
                'Statut article: ' . (string) ($article->status ?? ''),
                $this->sourceUrlForModel($article) ? 'Lien CRM: ' . $this->sourceUrlForModel($article) : null,
                '',
                'Actions recommandees',
                '-------------------',
                '- Verifier les ventes ou sorties recentes',
                '- Confirmer le besoin de reapprovisionnement',
                '- Lancer une commande fournisseur si necessaire',
                '- Documenter les decisions prises',
            ]));

            $document = $this->docxService->createDocument($tenantId, $title, $content);

            return [
                'result' => 'document_created',
                'message' => 'Document Google Docs créé pour cette alerte stock.',
                'document_id' => $document['document_id'] ?? null,
                'target_url' => $document['document_url'] ?? $this->routeUrl('google-docx.index'),
                'target_blank' => true,
            ];
        });
    }
}
