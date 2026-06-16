<?php

namespace Vendor\Automation\Actions;

use NexusExtensions\GoogleSheets\Services\GoogleSheetsService;
use RuntimeException;
use Throwable;
use Vendor\Automation\Models\AutomationEvent;
use Vendor\Automation\Models\AutomationSuggestion;
use Vendor\CrmCore\Models\TenantSetting;
use Vendor\Stock\Models\Article;
use Vendor\Client\Models\Client;
use Vendor\Invoice\Models\Invoice;
use Vendor\Stock\Models\DeliveryNote;
use Vendor\Stock\Models\Order;
use Vendor\Stock\Models\Supplier;

class AppendGoogleSheetRowAutomationAction extends AbstractAutomationAction
{
    public function __construct(
        \Vendor\Automation\Services\ExtensionAvailabilityService $extensions,
        protected GoogleSheetsService $sheetsService
    ) {
        parent::__construct($extensions);
    }

    public function execute(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion = null): array
    {
        return match ((string) $automationEvent->action_type) {
            'append_article_sheet_row' => $this->appendArticleRow($automationEvent, $suggestion),
            'append_client_sheet_row' => $this->appendClientRow($automationEvent, $suggestion),
            'append_invoice_sheet_row' => $this->appendInvoiceRow($automationEvent, $suggestion),
            'append_supplier_sheet_row' => $this->appendSupplierRow($automationEvent, $suggestion),
            'append_stock_order_sheet_row' => $this->appendStockOrderRow($automationEvent, $suggestion),
            'append_delivery_note_sheet_row' => $this->appendDeliveryNoteRow($automationEvent, $suggestion),
            'append_low_stock_sheet_row' => $this->appendLowStockRow($automationEvent, $suggestion),
            default => throw new RuntimeException('Type de synchronisation Google Sheets non pris en charge.'),
        };
    }

    protected function appendArticleRow(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-sheets', 'Google Sheets doit être installé pour enregistrer cet article.');

        return $this->withReconnectHandling('google-sheets', function () use ($automationEvent, $suggestion, $tenantId) {
            $payload = $this->payload($automationEvent);
            $articleId = $this->modelId($payload, $suggestion, 'article_id', Article::class);
            if (!$articleId) {
                throw new RuntimeException('Article introuvable pour la synchronisation Google Sheets.');
            }

            $article = $this->loadArticle($tenantId, $articleId);
            $sheet = $this->ensureSpreadsheet(
                $tenantId,
                'article_catalog',
                'CRM - Catalogue articles',
                'Articles',
                ['Date', 'Article ID', 'Nom', 'SKU', 'Unite', 'Prix achat', 'Prix vente', 'Stock courant', 'Seuil mini', 'Fournisseur', 'Statut', 'Source CRM']
            );

            $this->sheetsService->appendRows(
                $tenantId,
                $sheet['spreadsheet_id'],
                $sheet['sheet_title'] . '!A:L',
                [[
                    now()->format('Y-m-d H:i'),
                    (int) $article->id,
                    (string) $article->name,
                    (string) ($article->sku ?? ''),
                    (string) ($article->unit ?? ''),
                    (float) ($article->purchase_price ?? 0),
                    (float) ($article->sale_price ?? 0),
                    (float) $article->current_stock,
                    (float) $article->min_stock,
                    (string) optional($article->supplier)->name,
                    (string) ($article->status ?? ''),
                    (string) ($this->sourceUrlForModel($article) ?? ''),
                ]]
            );

            return [
                'result' => 'sheet_row_appended',
                'message' => 'Article ajouté au catalogue Google Sheets.',
                'spreadsheet_id' => $sheet['spreadsheet_id'],
                'target_url' => $sheet['spreadsheet_url'] ?? $this->routeUrl('google-sheets.index'),
                'target_blank' => true,
            ];
        });
    }

    protected function appendClientRow(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-sheets', 'Google Sheets doit être installé pour enregistrer ce client.');

        return $this->withReconnectHandling('google-sheets', function () use ($automationEvent, $suggestion, $tenantId) {
            $payload = $this->payload($automationEvent);
            $clientId = $this->modelId($payload, $suggestion, 'client_id', Client::class);
            if (!$clientId) {
                throw new RuntimeException('Client introuvable pour la synchronisation Google Sheets.');
            }

            $client = $this->loadClient($tenantId, $clientId);
            $sheet = $this->ensureSpreadsheet(
                $tenantId,
                'client_register',
                'CRM - Registre clients',
                'Clients',
                ['Date', 'Client ID', 'Societe', 'Contact', 'Email', 'Telephone', 'Statut', 'Type', 'Source CRM']
            );

            $this->sheetsService->appendRows(
                $tenantId,
                $sheet['spreadsheet_id'],
                $sheet['sheet_title'] . '!A:I',
                [[
                    now()->format('Y-m-d H:i'),
                    (int) $client->id,
                    (string) $client->company_name,
                    (string) ($client->contact_name ?? ''),
                    (string) ($client->email ?? ''),
                    (string) ($client->phone ?? ''),
                    (string) ($client->status ?? ''),
                    (string) ($client->type ?? ''),
                    (string) ($this->sourceUrlForModel($client) ?? ''),
                ]]
            );

            return [
                'result' => 'sheet_row_appended',
                'message' => 'Client ajouté au registre Google Sheets.',
                'spreadsheet_id' => $sheet['spreadsheet_id'],
                'target_url' => $sheet['spreadsheet_url'] ?? $this->routeUrl('google-sheets.index'),
                'target_blank' => true,
            ];
        });
    }

    protected function appendInvoiceRow(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-sheets', 'Google Sheets doit être installé pour enregistrer cette facture.');

        return $this->withReconnectHandling('google-sheets', function () use ($automationEvent, $suggestion, $tenantId) {
            $payload = $this->payload($automationEvent);
            $invoiceId = $this->modelId($payload, $suggestion, 'invoice_id', Invoice::class);
            if (!$invoiceId) {
                throw new RuntimeException('Facture introuvable pour la synchronisation Google Sheets.');
            }

            $invoice = $this->loadInvoice($tenantId, $invoiceId);
            $sheet = $this->ensureSpreadsheet(
                $tenantId,
                'invoice_tracker',
                'CRM - Suivi factures',
                'Factures',
                ['Date', 'Facture ID', 'Numero', 'Client', 'Email client', 'Statut', 'Echeance', 'Devise', 'Total', 'Source CRM']
            );

            $this->sheetsService->appendRows(
                $tenantId,
                $sheet['spreadsheet_id'],
                $sheet['sheet_title'] . '!A:J',
                [[
                    now()->format('Y-m-d H:i'),
                    (int) $invoice->id,
                    (string) $invoice->number,
                    (string) optional($invoice->client)->company_name,
                    (string) optional($invoice->client)->email,
                    (string) $invoice->status,
                    (string) ($invoice->due_date?->format('Y-m-d') ?? ''),
                    (string) $invoice->currency,
                    (float) $invoice->total,
                    (string) ($this->sourceUrlForModel($invoice) ?? ''),
                ]]
            );

            return [
                'result' => 'sheet_row_appended',
                'message' => 'Facture ajoutée au suivi Google Sheets.',
                'spreadsheet_id' => $sheet['spreadsheet_id'],
                'target_url' => $sheet['spreadsheet_url'] ?? $this->routeUrl('google-sheets.index'),
                'target_blank' => true,
            ];
        });
    }

    protected function appendSupplierRow(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-sheets', 'Google Sheets doit être installé pour enregistrer ce fournisseur.');

        return $this->withReconnectHandling('google-sheets', function () use ($automationEvent, $suggestion, $tenantId) {
            $payload = $this->payload($automationEvent);
            $supplierId = $this->modelId($payload, $suggestion, 'supplier_id', Supplier::class);
            if (!$supplierId) {
                throw new RuntimeException('Fournisseur introuvable pour la synchronisation Google Sheets.');
            }

            $supplier = $this->loadSupplier($tenantId, $supplierId);
            $sheet = $this->ensureSpreadsheet(
                $tenantId,
                'supplier_register',
                'CRM - Registre fournisseurs',
                'Fournisseurs',
                ['Date', 'Fournisseur ID', 'Nom', 'Contact', 'Email', 'Telephone', 'Ville', 'Pays', 'Source CRM']
            );

            $this->sheetsService->appendRows(
                $tenantId,
                $sheet['spreadsheet_id'],
                $sheet['sheet_title'] . '!A:I',
                [[
                    now()->format('Y-m-d H:i'),
                    (int) $supplier->id,
                    (string) $supplier->name,
                    (string) ($supplier->contact_name ?? ''),
                    (string) ($supplier->email ?? ''),
                    (string) ($supplier->phone ?? ''),
                    (string) ($supplier->city ?? ''),
                    (string) ($supplier->country ?? ''),
                    (string) ($this->sourceUrlForModel($supplier) ?? ''),
                ]]
            );

            return [
                'result' => 'sheet_row_appended',
                'message' => 'Fournisseur ajouté au registre Google Sheets.',
                'spreadsheet_id' => $sheet['spreadsheet_id'],
                'target_url' => $sheet['spreadsheet_url'] ?? $this->routeUrl('google-sheets.index'),
                'target_blank' => true,
            ];
        });
    }

    protected function appendStockOrderRow(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-sheets', 'Google Sheets doit être installé pour enregistrer cette commande fournisseur.');

        return $this->withReconnectHandling('google-sheets', function () use ($automationEvent, $suggestion, $tenantId) {
            $payload = $this->payload($automationEvent);
            $orderId = $this->modelId($payload, $suggestion, 'stock_order_id', Order::class);
            if (!$orderId) {
                throw new RuntimeException('Commande fournisseur introuvable pour la synchronisation Google Sheets.');
            }

            $order = $this->loadStockOrder($tenantId, $orderId);
            $sheet = $this->ensureSpreadsheet(
                $tenantId,
                'stock_order_register',
                'CRM - Commandes fournisseurs',
                'Commandes',
                ['Date', 'Commande ID', 'Numero', 'Fournisseur', 'Statut', 'Date commande', 'Date attendue', 'Reference', 'Total', 'Source CRM']
            );

            $this->sheetsService->appendRows(
                $tenantId,
                $sheet['spreadsheet_id'],
                $sheet['sheet_title'] . '!A:J',
                [[
                    now()->format('Y-m-d H:i'),
                    (int) $order->id,
                    (string) $order->number,
                    (string) optional($order->supplier)->name,
                    (string) $order->status,
                    (string) ($order->order_date?->format('Y-m-d') ?? ''),
                    (string) ($order->expected_date?->format('Y-m-d') ?? ''),
                    (string) ($order->reference ?? ''),
                    (float) $order->total,
                    (string) ($this->sourceUrlForModel($order) ?? ''),
                ]]
            );

            return [
                'result' => 'sheet_row_appended',
                'message' => 'Commande fournisseur ajoutée au registre Google Sheets.',
                'spreadsheet_id' => $sheet['spreadsheet_id'],
                'target_url' => $sheet['spreadsheet_url'] ?? $this->routeUrl('google-sheets.index'),
                'target_blank' => true,
            ];
        });
    }

    protected function appendDeliveryNoteRow(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-sheets', 'Google Sheets doit être installé pour enregistrer ce bon de livraison.');

        return $this->withReconnectHandling('google-sheets', function () use ($automationEvent, $suggestion, $tenantId) {
            $payload = $this->payload($automationEvent);
            $deliveryNoteId = $this->modelId($payload, $suggestion, 'delivery_note_id', DeliveryNote::class);
            if (!$deliveryNoteId) {
                throw new RuntimeException('Bon de livraison introuvable pour la synchronisation Google Sheets.');
            }

            $deliveryNote = $this->loadDeliveryNote($tenantId, $deliveryNoteId);
            $sheet = $this->ensureSpreadsheet(
                $tenantId,
                'delivery_note_register',
                'CRM - Registre bons de livraison',
                'Livraisons',
                ['Date', 'BL ID', 'Numero', 'Type', 'Statut', 'Tiers', 'Commande liée', 'Facture liée', 'Lignes', 'Source CRM']
            );

            $party = $deliveryNote->type === 'in'
                ? (string) optional($deliveryNote->supplier)->name
                : (string) optional($deliveryNote->client)->company_name;

            $this->sheetsService->appendRows(
                $tenantId,
                $sheet['spreadsheet_id'],
                $sheet['sheet_title'] . '!A:J',
                [[
                    now()->format('Y-m-d H:i'),
                    (int) $deliveryNote->id,
                    (string) $deliveryNote->number,
                    (string) $deliveryNote->type,
                    (string) $deliveryNote->status,
                    $party,
                    (string) optional($deliveryNote->order)->number,
                    (string) optional($deliveryNote->invoice)->number,
                    (int) $deliveryNote->items->count(),
                    (string) ($this->sourceUrlForModel($deliveryNote) ?? ''),
                ]]
            );

            return [
                'result' => 'sheet_row_appended',
                'message' => 'Bon de livraison ajouté au registre Google Sheets.',
                'spreadsheet_id' => $sheet['spreadsheet_id'],
                'target_url' => $sheet['spreadsheet_url'] ?? $this->routeUrl('google-sheets.index'),
                'target_blank' => true,
            ];
        });
    }

    protected function appendLowStockRow(AutomationEvent $automationEvent, ?AutomationSuggestion $suggestion): array
    {
        $tenantId = $this->tenantId($automationEvent);
        $this->assertExtensionActive($tenantId, 'google-sheets', 'Google Sheets doit être installé pour enregistrer cette alerte stock.');

        return $this->withReconnectHandling('google-sheets', function () use ($automationEvent, $suggestion, $tenantId) {
            $payload = $this->payload($automationEvent);
            $articleId = $this->modelId($payload, $suggestion, 'article_id', Article::class);
            if (!$articleId) {
                throw new RuntimeException('Article introuvable pour la synchronisation Google Sheets.');
            }

            $article = $this->loadArticle($tenantId, $articleId);
            $sheet = $this->ensureSpreadsheet(
                $tenantId,
                'low_stock_alerts',
                'CRM - Alertes stock',
                'Alertes',
                ['Date', 'Article ID', 'Article', 'SKU', 'Stock courant', 'Seuil mini', 'Fournisseur', 'Statut', 'Source CRM']
            );

            $this->sheetsService->appendRows(
                $tenantId,
                $sheet['spreadsheet_id'],
                $sheet['sheet_title'] . '!A:I',
                [[
                    now()->format('Y-m-d H:i'),
                    (int) $article->id,
                    (string) $article->name,
                    (string) ($article->sku ?? ''),
                    (float) $article->current_stock,
                    (float) $article->min_stock,
                    (string) optional($article->supplier)->name,
                    (string) ($article->status ?? ''),
                    (string) ($this->sourceUrlForModel($article) ?? ''),
                ]]
            );

            return [
                'result' => 'sheet_row_appended',
                'message' => 'Alerte stock ajoutée au registre Google Sheets.',
                'spreadsheet_id' => $sheet['spreadsheet_id'],
                'target_url' => $sheet['spreadsheet_url'] ?? $this->routeUrl('google-sheets.index'),
                'target_blank' => true,
            ];
        });
    }

    protected function ensureSpreadsheet(
        int $tenantId,
        string $templateKey,
        string $title,
        string $sheetTitle,
        array $headers
    ): array {
        $spreadsheetId = $this->templateSpreadsheetId($tenantId, $templateKey);
        $spreadsheet = null;

        if ($spreadsheetId !== null) {
            try {
                $spreadsheet = $this->sheetsService->getSpreadsheet($tenantId, $spreadsheetId);
            } catch (Throwable) {
                $spreadsheet = null;
            }
        }

        if (!$spreadsheet) {
            $spreadsheet = $this->sheetsService->createSpreadsheet($tenantId, $title, [$sheetTitle]);
            $this->storeTemplateSpreadsheetId($tenantId, $templateKey, (string) $spreadsheet['spreadsheet_id']);
        }

        $headerRange = sprintf('%s!A1:%s1', $sheetTitle, $this->columnLetter(count($headers)));
        $this->sheetsService->writeRange(
            $tenantId,
            (string) $spreadsheet['spreadsheet_id'],
            $headerRange,
            [$headers]
        );

        $spreadsheet['sheet_title'] = $sheetTitle;

        return $spreadsheet;
    }

    protected function templateSpreadsheetId(int $tenantId, string $templateKey): ?string
    {
        $value = TenantSetting::query()
            ->where('tenant_id', $tenantId)
            ->where('key', $this->templateSettingKey($templateKey))
            ->value('value');

        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : null;
    }

    protected function storeTemplateSpreadsheetId(int $tenantId, string $templateKey, string $spreadsheetId): void
    {
        TenantSetting::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'key' => $this->templateSettingKey($templateKey),
            ],
            [
                'value' => $spreadsheetId,
            ]
        );
    }

    protected function templateSettingKey(string $templateKey): string
    {
        return 'automation.google_sheets.' . $templateKey . '.spreadsheet_id';
    }

    protected function columnLetter(int $columnCount): string
    {
        $columnCount = max(1, $columnCount);
        $letters = '';

        while ($columnCount > 0) {
            $columnCount--;
            $letters = chr(65 + ($columnCount % 26)) . $letters;
            $columnCount = intdiv($columnCount, 26);
        }

        return $letters;
    }
}
