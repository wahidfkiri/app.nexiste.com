<?php

namespace App\Services\DataExports;

use App\Models\TenantDataExport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;
use Vendor\Client\Exports\ClientsExport;
use Vendor\Client\Models\Client;
use Vendor\CrmCore\Models\TenantSetting;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\Invoice\Exports\InvoicesExport;
use Vendor\Invoice\Exports\QuotesExport;
use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Models\Quote;
use Vendor\Stock\Exports\ArticlesExport;
use Vendor\Stock\Exports\DeliveryNotesExport;
use Vendor\Stock\Exports\OrdersExport;
use Vendor\Stock\Exports\StockMovementsExport;
use Vendor\Stock\Exports\SuppliersExport;
use Vendor\Stock\Models\Article;
use Vendor\Stock\Models\DeliveryNote;
use Vendor\Stock\Models\Order;
use Vendor\Stock\Models\StockMovement;
use Vendor\Stock\Models\Supplier;
use Vendor\User\Exports\UsersExport;
use Vendor\Automation\Services\ExtensionAvailabilityService;
use NexusExtensions\Dropbox\Models\DropboxToken;
use NexusExtensions\Dropbox\Services\DropboxService;
use NexusExtensions\GoogleDrive\Models\GoogleDriveToken;
use NexusExtensions\GoogleDrive\Services\GoogleDriveService;
use NexusExtensions\GoogleGmail\Models\GoogleGmailToken;
use NexusExtensions\GoogleGmail\Services\GoogleGmailService;
use ZipArchive;

class TenantDataExportService
{
    public function __construct(
        protected ExtensionAvailabilityService $extensions,
        protected GoogleDriveService $googleDrive,
        protected DropboxService $dropbox,
        protected GoogleGmailService $googleGmail
    ) {
    }

    public function start(User $user, string $provider): TenantDataExport
    {
        $tenantId = (int) $user->tenant_id;

        $running = TenantDataExport::query()
            ->forTenant($tenantId)
            ->forUser((int) $user->id)
            ->active()
            ->latest('id')
            ->first();

        if ($running) {
            return $running;
        }

        $steps = $this->steps();
        $timestamp = now()->format('Ymd-His');
        $fileName = sprintf('sauvegarde-crm-tenant-%d-%s.zip', $tenantId, $timestamp);
        $relativeWorkspace = sprintf('tenant-data-exports/export-%s', now()->format('YmdHisv') . '-' . $user->id);
        $absoluteWorkspace = storage_path('app/' . $relativeWorkspace);

        $export = TenantDataExport::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => (int) $user->id,
            'provider' => $provider,
            'status' => 'pending',
            'progress_percent' => 0,
            'total_steps' => count($steps),
            'current_step_index' => 0,
            'current_step_key' => $steps[0]['key'],
            'current_step_label' => $steps[0]['label'],
            'file_name' => $fileName,
            'workspace_path' => $absoluteWorkspace,
            'local_zip_path' => $absoluteWorkspace . DIRECTORY_SEPARATOR . $fileName,
            'meta' => [
                'logs' => [],
                'warnings' => [],
                'provider' => $provider,
                'relative_workspace' => $relativeWorkspace,
                'generated_files' => [],
            ],
        ]);

        return $export->fresh();
    }

    public function latestForUser(User $user): ?TenantDataExport
    {
        return TenantDataExport::query()
            ->forTenant((int) $user->tenant_id)
            ->forUser((int) $user->id)
            ->latest('id')
            ->first();
    }

    public function historyForUser(User $user, int $limit = 8): array
    {
        return TenantDataExport::query()
            ->forTenant((int) $user->tenant_id)
            ->forUser((int) $user->id)
            ->whereIn('status', ['completed', 'failed'])
            ->latest('completed_at')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (TenantDataExport $export) => $this->presentHistoryItem($export, $user))
            ->values()
            ->all();
    }

    public function advance(TenantDataExport $export, User $user): TenantDataExport
    {
        $this->guardOwnership($export, $user);

        if (in_array($export->status, ['completed', 'failed'], true)) {
            return $export->fresh();
        }

        $steps = $this->steps();
        $currentIndex = (int) ($export->current_step_index ?? 0);
        $step = $steps[$currentIndex] ?? null;

        if ($step === null) {
            return $this->markCompleted($export, $user);
        }

        $export->forceFill([
            'status' => 'running',
            'started_at' => $export->started_at ?: now(),
            'current_step_key' => $step['key'],
            'current_step_label' => $step['label'],
        ])->save();

        try {
            $method = $step['method'];
            $this->{$method}($export, $user);

            $nextIndex = $currentIndex + 1;
            if (!isset($steps[$nextIndex])) {
                return $this->markCompleted($export, $user);
            }

            $nextStep = $steps[$nextIndex];
            $progress = (int) floor(($nextIndex / count($steps)) * 100);

            $export->forceFill([
                'status' => 'running',
                'progress_percent' => $progress,
                'current_step_index' => $nextIndex,
                'current_step_key' => $nextStep['key'],
                'current_step_label' => $nextStep['label'],
            ])->save();

            return $export->fresh();
        } catch (Throwable $e) {
            $publicMessage = $this->publicErrorMessage($e->getMessage(), $step['key'] ?? null);

            Log::error('[TenantDataExport] export step failed', [
                'export_id' => $export->id,
                'tenant_id' => $export->tenant_id,
                'user_id' => $export->user_id,
                'provider' => $export->provider,
                'step_key' => $step['key'] ?? null,
                'step_label' => $step['label'] ?? null,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            $this->appendLog($export, $step['key'], $step['label'], 'error', $publicMessage);
            $export->forceFill([
                'status' => 'failed',
                'error_message' => $publicMessage,
                'current_step_key' => $step['key'],
                'current_step_label' => $step['label'],
                'completed_at' => now(),
            ])->save();

            return $export->fresh();
        }
    }

    public function present(TenantDataExport $export, User $user): array
    {
        $this->guardOwnership($export, $user);

        $providerState = $this->providerState((int) $export->tenant_id, (string) $export->provider);
        $steps = $this->steps();
        $currentIndex = (int) ($export->current_step_index ?? 0);

        $timeline = collect($steps)->map(function (array $step, int $index) use ($export, $currentIndex) {
            $status = 'pending';

            if ($export->status === 'completed') {
                $status = 'completed';
            } elseif ($export->status === 'failed' && $export->current_step_key === $step['key']) {
                $status = 'failed';
            } elseif ($index < $currentIndex) {
                $status = 'completed';
            } elseif (in_array($export->status, ['pending', 'running'], true) && $index === $currentIndex) {
                $status = 'running';
            }

            return [
                'key' => $step['key'],
                'label' => $step['label'],
                'description' => $step['description'],
                'status' => $status,
            ];
        })->values()->all();

        return [
            'id' => (int) $export->id,
            'provider' => [
                'slug' => (string) $export->provider,
                'label' => $providerState['label'],
                'icon' => $providerState['icon'],
                'installed' => $providerState['installed'],
                'connected' => $providerState['connected'],
                'ready' => $providerState['ready'],
                'action_url' => $providerState['action_url'],
                'action_label' => $providerState['action_label'],
            ],
            'status' => (string) $export->status,
            'status_label' => $this->statusLabel((string) $export->status),
            'progress_percent' => (int) $export->progress_percent,
            'current_step_label' => (string) ($export->current_step_label ?? ''),
            'current_step_key' => (string) ($export->current_step_key ?? ''),
            'total_steps' => (int) $export->total_steps,
            'file_name' => $export->file_name,
            'remote_url' => $export->remote_url,
            'remote_file_id' => $export->remote_file_id,
            'error_message' => $this->publicErrorMessage((string) ($export->error_message ?? ''), (string) ($export->current_step_key ?? '')),
            'logs' => array_values((array) data_get($export->meta, 'logs', [])),
            'warnings' => array_values((array) data_get($export->meta, 'warnings', [])),
            'generated_files' => array_values((array) data_get($export->meta, 'generated_files', [])),
            'timeline' => $timeline,
            'can_continue' => in_array($export->status, ['pending', 'running'], true),
            'can_restart' => in_array($export->status, ['failed', 'completed'], true),
            'started_at' => optional($export->started_at)->toIso8601String(),
            'completed_at' => optional($export->completed_at)->toIso8601String(),
        ];
    }

    public function presentHistoryItem(TenantDataExport $export, User $user): array
    {
        $this->guardOwnership($export, $user);

        $providerState = $this->providerState((int) $export->tenant_id, (string) $export->provider);
        $referenceDate = $export->completed_at ?: $export->started_at ?: $export->created_at;
        $timezone = (string) ($user->timezone ?: config('app.timezone', 'UTC'));

        return [
            'id' => (int) $export->id,
            'provider' => [
                'slug' => (string) $export->provider,
                'label' => $providerState['label'],
                'icon' => $providerState['icon'],
            ],
            'status' => (string) $export->status,
            'status_label' => $this->statusLabel((string) $export->status),
            'file_name' => (string) ($export->file_name ?? ''),
            'remote_url' => (string) ($export->remote_url ?? ''),
            'reference_date' => optional($referenceDate)?->toIso8601String(),
            'reference_date_label' => $referenceDate
                ? $referenceDate->copy()->timezone($timezone)->format('d/m/Y H:i')
                : '',
            'error_message' => $this->publicErrorMessage((string) ($export->error_message ?? ''), (string) ($export->current_step_key ?? '')),
        ];
    }

    public function providerStates(int $tenantId): array
    {
        return [
            $this->providerState($tenantId, 'google-drive'),
            $this->providerState($tenantId, 'dropbox'),
        ];
    }

    protected function steps(): array
    {
        return [
            [
                'key' => 'prepare',
                'label' => 'Préparation des fichiers de sauvegarde',
                'description' => 'Connexion vérifiée et préparation des fichiers avant la génération complète de l archive.',
                'method' => 'prepareWorkspace',
            ],
            [
                'key' => 'export_crm_excel',
                'label' => 'Export Excel des donnees CRM',
                'description' => 'Clients, utilisateurs et donnees transverses en format tableur.',
                'method' => 'exportCrmExcels',
            ],
            [
                'key' => 'export_stock_excel',
                'label' => 'Export Excel du stock et des achats',
                'description' => 'Articles, fournisseurs, commandes, bons de livraison et mouvements de stock.',
                'method' => 'exportStockExcels',
            ],
            [
                'key' => 'export_billing_excel',
                'label' => 'Export Excel de la facturation',
                'description' => 'Factures et devis dans des fichiers faciles a auditer.',
                'method' => 'exportBillingExcels',
            ],
            [
                'key' => 'generate_invoice_quote_pdfs',
                'label' => 'Generation des PDF factures et devis',
                'description' => 'Preparation des PDF individuels pour l archivage documentaire.',
                'method' => 'generateInvoiceAndQuotePdfs',
            ],
            [
                'key' => 'generate_delivery_note_pdfs',
                'label' => 'Generation des PDF des bons de livraison',
                'description' => 'Creation des BL PDF pour la tracabilite logistique.',
                'method' => 'generateDeliveryNotePdfs',
            ],
            [
                'key' => 'export_sensitive_metadata',
                'label' => 'Export des donnees sensibles et des metadonnees',
                'description' => 'Parametres, extensions actives et resume de l espace de travail sans secrets OAuth.',
                'method' => 'exportSensitiveMetadata',
            ],
            [
                'key' => 'archive_and_upload',
                'label' => 'Compression et envoi vers le stockage choisi',
                'description' => 'Construction du fichier ZIP final puis envoi vers Google Drive ou Dropbox.',
                'method' => 'archiveAndUpload',
            ],
        ];
    }

    protected function prepareWorkspace(TenantDataExport $export, User $user): void
    {
        $providerState = $this->providerState((int) $export->tenant_id, (string) $export->provider);
        if (!$providerState['installed']) {
            throw new RuntimeException($providerState['missing_message']);
        }
        if (!$providerState['connected']) {
            throw new RuntimeException($providerState['reconnect_message']);
        }

        $workspace = $this->workspacePath($export);
        File::ensureDirectoryExists($workspace . DIRECTORY_SEPARATOR . 'excel');
        File::ensureDirectoryExists($workspace . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . 'factures');
        File::ensureDirectoryExists($workspace . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . 'devis');
        File::ensureDirectoryExists($workspace . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . 'bons-livraison');
        File::ensureDirectoryExists($workspace . DIRECTORY_SEPARATOR . 'meta');

        $this->appendLog(
            $export,
            'prepare',
            'Préparation des fichiers de sauvegarde',
            'completed',
            'Connexion ' . $providerState['label'] . ' vérifiée. Préparation des fichiers de sauvegarde en cours.'
        );
    }

    protected function exportCrmExcels(TenantDataExport $export, User $user): void
    {
        $relativeBase = $this->relativeWorkspacePath($export);

        Excel::store(new ClientsExport(), $relativeBase . '/excel/clients.xlsx', 'local');
        Excel::store(new UsersExport(), $relativeBase . '/excel/utilisateurs.xlsx', 'local');

        $this->recordGeneratedFiles($export, [
            'excel/clients.xlsx',
            'excel/utilisateurs.xlsx',
        ]);

        $this->appendLog(
            $export,
            'export_crm_excel',
            'Export Excel des donnees CRM',
            'completed',
            sprintf('%d clients et %d utilisateurs exportes en Excel.', Client::query()->count(), $this->tenantUsers($user)->count())
        );
    }

    protected function exportStockExcels(TenantDataExport $export, User $user): void
    {
        $relativeBase = $this->relativeWorkspacePath($export);

        Excel::store(new SuppliersExport(), $relativeBase . '/excel/fournisseurs.xlsx', 'local');
        Excel::store(new ArticlesExport(), $relativeBase . '/excel/articles.xlsx', 'local');
        Excel::store(new OrdersExport(), $relativeBase . '/excel/commandes-fournisseurs.xlsx', 'local');
        Excel::store(new DeliveryNotesExport(), $relativeBase . '/excel/bons-livraison.xlsx', 'local');
        Excel::store(new StockMovementsExport(), $relativeBase . '/excel/mouvements-stock.xlsx', 'local');

        $this->recordGeneratedFiles($export, [
            'excel/fournisseurs.xlsx',
            'excel/articles.xlsx',
            'excel/commandes-fournisseurs.xlsx',
            'excel/bons-livraison.xlsx',
            'excel/mouvements-stock.xlsx',
        ]);

        $this->appendLog(
            $export,
            'export_stock_excel',
            'Export Excel du stock et des achats',
            'completed',
            sprintf(
                '%d fournisseurs, %d articles, %d commandes, %d BL et %d mouvements exportes.',
                Supplier::query()->count(),
                Article::query()->count(),
                Order::query()->count(),
                DeliveryNote::query()->count(),
                StockMovement::query()->count()
            )
        );
    }

    protected function exportBillingExcels(TenantDataExport $export, User $user): void
    {
        $relativeBase = $this->relativeWorkspacePath($export);

        Excel::store(new InvoicesExport(), $relativeBase . '/excel/factures.xlsx', 'local');
        Excel::store(new QuotesExport(), $relativeBase . '/excel/devis.xlsx', 'local');

        $this->recordGeneratedFiles($export, [
            'excel/factures.xlsx',
            'excel/devis.xlsx',
        ]);

        $this->appendLog(
            $export,
            'export_billing_excel',
            'Export Excel de la facturation',
            'completed',
            sprintf('%d factures et %d devis exportes en Excel.', Invoice::query()->count(), Quote::query()->count())
        );
    }

    protected function generateInvoiceAndQuotePdfs(TenantDataExport $export, User $user): void
    {
        $settings = $this->invoiceSettings((int) $user->tenant_id);
        $branding = $this->resolveInvoiceBranding($settings, $user);
        $warnings = [];
        $generated = [];

        $invoices = Invoice::query()->with(['client', 'items', 'payments', 'tenant'])->orderBy('id')->get();
        foreach ($invoices as $invoice) {
            try {
                $signature = [
                    'enabled' => filter_var($settings['signature_enabled'] ?? false, FILTER_VALIDATE_BOOL),
                    'data' => $settings['signature_data'] ?? null,
                    'name' => $settings['signer_name'] ?? null,
                    'title' => $settings['signer_title'] ?? null,
                    'show_on_invoice' => filter_var($settings['signature_on_invoice'] ?? true, FILTER_VALIDATE_BOOL),
                ];
                $template = (string) ($settings['pdf_invoice_template'] ?? 'classic');
                $view = match ($template) {
                    'modern' => 'invoice::exports.pdf_invoice_modern',
                    'minimal' => 'invoice::exports.pdf_invoice_minimal',
                    default => 'invoice::exports.pdf_invoice',
                };

                $pdf = app('dompdf.wrapper')
                    ->loadView($view, compact('invoice', 'signature', 'branding'))
                    ->setPaper($settings['pdf_paper'] ?? 'A4');

                $safeNumber = preg_replace('/[^A-Za-z0-9\\-_]/', '-', (string) $invoice->number);
                $relative = 'pdf/factures/facture-' . $safeNumber . '.pdf';
                File::put($this->workspacePath($export) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative), $pdf->output());
                $generated[] = $relative;
            } catch (Throwable $e) {
                $warnings[] = 'Facture ' . ($invoice->number ?: $invoice->id) . ': ' . $e->getMessage();
            }
        }

        $quotes = Quote::query()->with(['client', 'items', 'tenant'])->orderBy('id')->get();
        foreach ($quotes as $quote) {
            try {
                $signature = [
                    'enabled' => filter_var($settings['signature_enabled'] ?? false, FILTER_VALIDATE_BOOL),
                    'data' => $settings['signature_data'] ?? null,
                    'name' => $settings['signer_name'] ?? null,
                    'title' => $settings['signer_title'] ?? null,
                    'show_on_quote' => filter_var($settings['signature_on_quote'] ?? true, FILTER_VALIDATE_BOOL),
                ];
                $template = (string) ($settings['pdf_quote_template'] ?? 'classic');
                $view = match ($template) {
                    'modern' => 'invoice::exports.pdf_quote_modern',
                    'minimal' => 'invoice::exports.pdf_quote_minimal',
                    default => 'invoice::exports.pdf_quote',
                };

                $pdf = app('dompdf.wrapper')
                    ->loadView($view, compact('quote', 'signature', 'branding'))
                    ->setPaper($settings['pdf_paper'] ?? 'A4');

                $safeNumber = preg_replace('/[^A-Za-z0-9\\-_]/', '-', (string) $quote->number);
                $relative = 'pdf/devis/devis-' . $safeNumber . '.pdf';
                File::put($this->workspacePath($export) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative), $pdf->output());
                $generated[] = $relative;
            } catch (Throwable $e) {
                $warnings[] = 'Devis ' . ($quote->number ?: $quote->id) . ': ' . $e->getMessage();
            }
        }

        $this->recordGeneratedFiles($export, $generated);
        $this->appendWarnings($export, $warnings);
        $this->appendLog(
            $export,
            'generate_invoice_quote_pdfs',
            'Generation des PDF factures et devis',
            'completed',
            sprintf('%d PDF factures/devis generes, %d avertissement(s).', count($generated), count($warnings))
        );
    }

    protected function generateDeliveryNotePdfs(TenantDataExport $export, User $user): void
    {
        $generated = [];
        $warnings = [];

        $deliveryNotes = DeliveryNote::query()
            ->with(['supplier', 'client', 'order', 'invoice', 'items.article', 'creator', 'validator'])
            ->orderBy('id')
            ->get();

        foreach ($deliveryNotes as $deliveryNote) {
            try {
                $pdf = app('dompdf.wrapper')
                    ->loadView('stock::delivery-notes.pdf', compact('deliveryNote'))
                    ->setPaper('A4');

                $safeNumber = preg_replace('/[^A-Za-z0-9\\-_]/', '-', (string) $deliveryNote->number);
                $relative = 'pdf/bons-livraison/bon-livraison-' . $safeNumber . '.pdf';
                File::put($this->workspacePath($export) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative), $pdf->output());
                $generated[] = $relative;
            } catch (Throwable $e) {
                $warnings[] = 'BL ' . ($deliveryNote->number ?: $deliveryNote->id) . ': ' . $e->getMessage();
            }
        }

        $this->recordGeneratedFiles($export, $generated);
        $this->appendWarnings($export, $warnings);
        $this->appendLog(
            $export,
            'generate_delivery_note_pdfs',
            'Generation des PDF des bons de livraison',
            'completed',
            sprintf('%d PDF BL generes, %d avertissement(s).', count($generated), count($warnings))
        );
    }

    protected function exportSensitiveMetadata(TenantDataExport $export, User $user): void
    {
        $tenant = $user->tenant()->firstOrFail();

        $this->writeJson($export, 'meta/tenant.json', [
            'id' => (int) $tenant->id,
            'name' => (string) $tenant->name,
            'email' => (string) ($tenant->email ?? ''),
            'phone' => (string) ($tenant->phone ?? ''),
            'address' => (string) ($tenant->address ?? ''),
            'timezone' => (string) ($tenant->timezone ?? ''),
            'locale' => (string) ($tenant->locale ?? ''),
            'currency' => (string) ($tenant->currency ?? ''),
            'created_at' => optional($tenant->created_at)->toIso8601String(),
        ]);

        $this->writeJson($export, 'meta/tenant-settings.json', TenantSetting::query()
            ->where('tenant_id', (int) $tenant->id)
            ->orderBy('key')
            ->get(['key', 'value'])
            ->map(fn ($row) => ['key' => (string) $row->key, 'value' => $row->value])
            ->values()
            ->all());

        $users = $this->tenantUsers($user)->map(function (User $member) {
            return [
                'id' => (int) $member->id,
                'name' => (string) $member->name,
                'email' => (string) $member->email,
                'phone' => (string) ($member->phone ?? ''),
                'job_title' => (string) ($member->job_title ?? ''),
                'department' => (string) ($member->department ?? ''),
                'status' => (string) ($member->status ?? ''),
                'last_login_at' => optional($member->last_login_at)->toIso8601String(),
                'created_at' => optional($member->created_at)->toIso8601String(),
            ];
        })->values()->all();
        $this->writeJson($export, 'meta/users.json', $users);

        $extensions = TenantExtension::query()
            ->forTenant((int) $tenant->id)
            ->with('extension:id,slug,name,status')
            ->orderByDesc('activated_at')
            ->get()
            ->map(function (TenantExtension $activation) {
                return [
                    'slug' => (string) ($activation->extension?->slug ?? ''),
                    'name' => (string) ($activation->extension?->name ?? ''),
                    'catalog_status' => (string) ($activation->extension?->status ?? ''),
                    'tenant_status' => (string) $activation->status,
                    'activated_at' => optional($activation->activated_at)->toIso8601String(),
                    'deactivated_at' => optional($activation->deactivated_at)->toIso8601String(),
                    'settings' => (array) ($activation->settings ?? []),
                ];
            })
            ->values()
            ->all();
        $this->writeJson($export, 'meta/extensions.json', $extensions);

        $summary = [
            'clients' => Client::query()->count(),
            'suppliers' => Supplier::query()->count(),
            'articles' => Article::query()->count(),
            'purchase_orders' => Order::query()->count(),
            'delivery_notes' => DeliveryNote::query()->count(),
            'stock_movements' => StockMovement::query()->count(),
            'invoices' => Invoice::query()->count(),
            'quotes' => Quote::query()->count(),
            'users' => count($users),
        ];
        $this->writeJson($export, 'meta/database-summary.json', $summary);

        $manifest = [
            'generated_at' => now()->toIso8601String(),
            'provider' => $export->provider,
            'file_name' => $export->file_name,
            'generated_files' => array_values((array) data_get($export->meta, 'generated_files', [])),
            'warnings' => array_values((array) data_get($export->meta, 'warnings', [])),
            'summary' => $summary,
        ];
        $this->writeJson($export, 'meta/export-manifest.json', $manifest);

        $this->recordGeneratedFiles($export, [
            'meta/tenant.json',
            'meta/tenant-settings.json',
            'meta/users.json',
            'meta/extensions.json',
            'meta/database-summary.json',
            'meta/export-manifest.json',
        ]);

        $this->appendLog(
            $export,
            'export_sensitive_metadata',
            'Export des donnees sensibles et des metadonnees',
            'completed',
            'Parametres, utilisateurs, extensions actives et resume metier exportes en JSON.'
        );
    }

    protected function archiveAndUpload(TenantDataExport $export, User $user): void
    {
        $zipPath = $this->createArchive($export);
        $result = $this->uploadArchive($export, $zipPath, $user);

        $export->forceFill([
            'remote_file_id' => (string) ($result['id'] ?? ''),
            'remote_url' => (string) ($result['web_view_link'] ?? $result['webViewLink'] ?? ''),
        ])->save();

        $this->appendLog(
            $export,
            'archive_and_upload',
            'Compression et envoi vers le stockage choisi',
            'completed',
            'Archive ZIP envoyee avec succes vers ' . $this->providerLabel((string) $export->provider) . '.'
        );
    }

    protected function createArchive(TenantDataExport $export): string
    {
        $workspace = $this->workspacePath($export);
        $zipPath = $export->local_zip_path ?: ($workspace . DIRECTORY_SEPARATOR . $export->file_name);

        if (is_file($zipPath)) {
            @unlink($zipPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Impossible de creer l archive ZIP.');
        }

        $rootLength = strlen($workspace) + 1;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($workspace, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $absolute = $item->getPathname();
            if ($absolute === $zipPath) {
                continue;
            }

            $localName = substr($absolute, $rootLength);
            if ($localName === false || $localName === '') {
                continue;
            }

            $localName = str_replace(DIRECTORY_SEPARATOR, '/', $localName);

            if ($item->isDir()) {
                $zip->addEmptyDir($localName);
                continue;
            }

            $zip->addFile($absolute, $localName);
        }

        $zip->close();

        if (!is_file($zipPath)) {
            throw new RuntimeException('L archive ZIP n a pas pu etre generee.');
        }

        return $zipPath;
    }

    protected function uploadArchive(TenantDataExport $export, string $zipPath, User $user): array
    {
        $provider = (string) $export->provider;
        $uploadedFile = new UploadedFile(
            $zipPath,
            basename($zipPath),
            'application/zip',
            null,
            true
        );

        if ($provider === 'google-drive') {
            $result = $this->googleDrive->uploadFile((int) $export->tenant_id, $uploadedFile);
        } elseif ($provider === 'dropbox') {
            $result = $this->dropbox->uploadFile((int) $export->tenant_id, $uploadedFile);
            $result = $this->enrichDropboxArchiveLink($export, is_array($result) ? $result : []);
        } else {
            throw new RuntimeException('Destination de sauvegarde non supportee.');
        }

        return is_array($result) ? $result : [];
    }

    protected function enrichDropboxArchiveLink(TenantDataExport $export, array $result): array
    {
        $fileId = (string) ($result['id'] ?? '');
        if ($fileId === '') {
            return $result;
        }

        try {
            $shared = $this->dropbox->share((int) $export->tenant_id, $fileId);

            $result['web_view_link'] = (string) ($shared['web_view_link'] ?? $shared['download_link'] ?? $result['web_view_link'] ?? '');
            $result['download_link'] = (string) ($shared['download_link'] ?? $shared['web_view_link'] ?? $result['download_link'] ?? '');

            return $result;
        } catch (\Throwable $e) {
            try {
                $temporaryUrl = $this->dropbox->getOpenUrl((int) $export->tenant_id, $fileId);
                if ($temporaryUrl !== '') {
                    $result['web_view_link'] = $temporaryUrl;
                }
            } catch (\Throwable $fallback) {
                // Keep the upload successful even if Dropbox cannot generate an open URL.
            }

            $this->appendWarnings($export, [
                'Dropbox a bien recu l archive, mais le lien partageable n a pas pu etre cree automatiquement. Ouvrez Dropbox pour retrouver le fichier si necessaire.',
            ]);

            return $result;
        }
    }

    protected function markCompleted(TenantDataExport $export, User $user): TenantDataExport
    {
        $workspace = $this->workspacePath($export);

        $export->forceFill([
            'status' => 'completed',
            'progress_percent' => 100,
            'current_step_index' => count($this->steps()),
            'current_step_key' => 'completed',
            'current_step_label' => 'Sauvegarde terminee',
            'completed_at' => now(),
            'error_message' => null,
        ])->save();

        if (is_dir($workspace)) {
            File::deleteDirectory($workspace);
        }

        $export->forceFill([
            'workspace_path' => null,
            'local_zip_path' => null,
        ])->save();

        $this->maybeSendCompletionEmailNotification($export->fresh(), $user);

        return $export->fresh();
    }

    protected function maybeSendCompletionEmailNotification(TenantDataExport $export, User $user): void
    {
        $tenantId = (int) $export->tenant_id;

        if (!$this->extensions->isActive($tenantId, 'google-gmail')) {
            return;
        }

        $token = GoogleGmailToken::forTenant($tenantId)->active()->first();
        if (!$token) {
            return;
        }

        $recipient = $this->notificationRecipient($user, $token);
        if ($recipient === null) {
            return;
        }

        $completedAt = ($export->completed_at ?? now())->copy()->timezone((string) ($user->timezone ?: config('app.timezone', 'UTC')));
        $providerLabel = $this->providerLabel((string) $export->provider);
        $userName = trim((string) ($user->name ?: $user->email ?: ''));
        $greetingName = $userName !== '' ? $userName : 'Bonjour';
        $archiveName = (string) ($export->file_name ?: 'archive ZIP');
        $openUrl = trim((string) ($export->remote_url ?? ''));
        $historyUrl = Route::has('settings.global') ? route('settings.global') : null;

        $bodyText = implode("\n\n", array_filter([
            "Bonjour {$greetingName},",
            "Votre sauvegarde CRM a bien ete exportee vers {$providerLabel}.",
            "Archive : {$archiveName}",
            'Date : ' . $completedAt->format('d/m/Y H:i'),
            $openUrl !== '' ? "Lien : {$openUrl}" : null,
            $historyUrl ? "Vous pouvez aussi retrouver l historique des sauvegardes ici : {$historyUrl}" : null,
        ]));

        $bodyHtml = '<p>Bonjour ' . e($greetingName) . ',</p>'
            . '<p>Votre sauvegarde CRM a bien ete exportee vers <strong>' . e($providerLabel) . '</strong>.</p>'
            . '<ul>'
            . '<li><strong>Archive :</strong> ' . e($archiveName) . '</li>'
            . '<li><strong>Date :</strong> ' . e($completedAt->format('d/m/Y H:i')) . '</li>'
            . '</ul>';

        if ($openUrl !== '') {
            $bodyHtml .= '<p><a href="' . e($openUrl) . '">Ouvrir l archive exportee</a></p>';
        } elseif ($historyUrl) {
            $bodyHtml .= '<p><a href="' . e($historyUrl) . '">Voir l historique des sauvegardes</a></p>';
        }

        try {
            $this->googleGmail->sendEmail($tenantId, [
                'to' => $recipient,
                'subject' => 'Sauvegarde CRM terminee - ' . $completedAt->format('d/m/Y H:i'),
                'body_text' => $bodyText,
                'body_html' => $bodyHtml,
            ]);

            $this->appendLog(
                $export,
                'notify_completion_email',
                'Notification email de sauvegarde',
                'completed',
                'Notification envoyee via Google Gmail a ' . $recipient . '.'
            );
        } catch (Throwable $e) {
            Log::warning('[TenantDataExport] gmail notification failed', [
                'export_id' => $export->id,
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            $warning = str_contains(mb_strtolower($e->getMessage()), 'session google gmail')
                ? 'La sauvegarde est terminee, mais Google Gmail doit etre reconnecte pour envoyer la notification email.'
                : 'La sauvegarde est terminee, mais la notification email Gmail n a pas pu etre envoyee.';

            $this->appendWarnings($export, [$warning]);
            $this->appendLog(
                $export,
                'notify_completion_email',
                'Notification email de sauvegarde',
                'warning',
                $warning
            );
        }
    }

    protected function notificationRecipient(User $user, ?GoogleGmailToken $token = null): ?string
    {
        $candidates = array_filter([
            trim((string) $user->email),
            trim((string) ($token?->google_email ?? '')),
            trim((string) optional($user->tenant)->email),
        ]);

        foreach ($candidates as $candidate) {
            if (filter_var($candidate, FILTER_VALIDATE_EMAIL) !== false) {
                return $candidate;
            }
        }

        return null;
    }

    protected function providerState(int $tenantId, string $provider): array
    {
        $installed = $this->extensions->isActive($tenantId, $provider);
        $connected = match ($provider) {
            'google-drive' => GoogleDriveToken::forTenant($tenantId)->active()->exists(),
            'dropbox' => DropboxToken::forTenant($tenantId)->active()->exists(),
            default => false,
        };

        $label = $this->providerLabel($provider);
        $actionUrl = $installed
            ? $this->extensions->targetUrl($provider)
            : (Route::has('marketplace.show') ? route('marketplace.show', $provider) : $this->extensions->targetUrl($provider));

        return [
            'slug' => $provider,
            'label' => $label,
            'icon' => $provider === 'google-drive' ? 'fab fa-google-drive' : 'fab fa-dropbox',
            'installed' => $installed,
            'connected' => $connected,
            'ready' => $installed && $connected,
            'action_url' => $actionUrl,
            'action_label' => !$installed ? 'Installer ' . $label : 'Reconnecter ' . $label,
            'missing_message' => $label . ' n est pas installe pour ce tenant. Activez l extension avant de lancer la sauvegarde.',
            'reconnect_message' => $label . ' est installe mais la session n est pas connectee. Reconnectez le service puis relancez la sauvegarde.',
        ];
    }

    protected function providerLabel(string $provider): string
    {
        return match ($provider) {
            'google-drive' => 'Google Drive',
            'dropbox' => 'Dropbox',
            default => ucfirst(str_replace('-', ' ', $provider)),
        };
    }

    protected function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'En attente',
            'running' => 'En cours',
            'completed' => 'Terminee',
            'failed' => 'Echouee',
            default => ucfirst($status),
        };
    }

    public function publicErrorMessage(?string $message, ?string $stepKey = null): string
    {
        $message = trim((string) $message);
        if ($message === '') {
            return 'La sauvegarde n a pas pu aboutir. Relancez l export apres verification.';
        }

        $normalized = mb_strtolower($message);

        if (str_contains($normalized, 'n est pas installe pour ce tenant')
            || str_contains($normalized, 'session n est pas connectee')
            || str_contains($normalized, 'reconnectez le service')
            || str_contains($normalized, 'dropbox demande une reconnexion')
            || str_contains($normalized, 'session dropbox expiree')
            || str_contains($normalized, 'session google drive expiree')
        ) {
            return $message;
        }

        if (str_contains($normalized, 'sqlstate[')
            || str_contains($normalized, 'return value must be of type')
            || str_contains($normalized, 'vendor\\')
            || str_contains($normalized, 'getsourcelabelattribute')
            || str_contains($normalized, 'call to undefined')
            || str_contains($normalized, 'failed to open stream')
        ) {
            return match ($stepKey) {
                'export_crm_excel' => 'Certaines donnees CRM sont invalides ou incompletes pour l export Excel. Corrigez les fiches concernees puis relancez la sauvegarde.',
                'export_stock_excel' => 'Certaines donnees stock ou achats sont invalides pour l export. Corrigez les enregistrements concernes puis relancez la sauvegarde.',
                'export_billing_excel', 'generate_invoice_quote_pdfs', 'generate_delivery_note_pdfs'
                    => 'Certaines donnees de facturation ou de livraison sont invalides pour la sauvegarde. Corrigez les documents concernes puis relancez l export.',
                default => 'Certaines donnees de l application sont invalides ou incompletes pour cette sauvegarde. Corrigez-les puis relancez l export.',
            };
        }

        if (str_contains($normalized, 'curl error 28')
            || str_contains($normalized, 'timeout was reached')
            || str_contains($normalized, 'failed to connect to content.dropboxapi.com')
        ) {
            return 'La connexion avec Dropbox a pris trop de temps pendant l envoi de l archive. Verifiez votre connexion Internet puis relancez la sauvegarde.';
        }

        if (str_contains($normalized, 'impossible de creer l archive zip')
            || str_contains($normalized, 'archive zip n a pas pu etre generee')) {
            return 'Le fichier ZIP final n a pas pu etre genere. Relancez la sauvegarde apres verification de l espace disque.';
        }

        if (str_contains($normalized, 'destination de sauvegarde non supportee')) {
            return 'La destination de sauvegarde choisie n est pas prise en charge.';
        }

        return $message;
    }

    protected function guardOwnership(TenantDataExport $export, User $user): void
    {
        if ((int) $export->tenant_id !== (int) $user->tenant_id || (int) $export->user_id !== (int) $user->id) {
            abort(404);
        }
    }

    protected function invoiceSettings(int $tenantId): array
    {
        $rows = TenantSetting::query()
            ->where('tenant_id', $tenantId)
            ->where('key', 'like', 'invoice.%')
            ->get(['key', 'value']);

        $settings = [];
        foreach ($rows as $row) {
            $shortKey = str_replace('invoice.', '', (string) $row->key);
            $value = $row->value;
            if (is_string($value) && ($value !== '') && (str_starts_with($value, '[') || str_starts_with($value, '{'))) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $value = $decoded;
                }
            }
            $settings[$shortKey] = $value;
        }

        foreach ([
            'signature_enabled',
            'signature_on_invoice',
            'signature_on_quote',
            'pdf_show_bank',
            'pdf_show_footer',
            'pdf_show_logo',
            'pdf_watermark_draft',
        ] as $boolKey) {
            if (array_key_exists($boolKey, $settings)) {
                $settings[$boolKey] = filter_var($settings[$boolKey], FILTER_VALIDATE_BOOL);
            }
        }

        $settings['pdf_theme'] = $settings['pdf_theme'] ?? 'ocean';
        $settings['pdf_show_footer'] = $settings['pdf_show_footer'] ?? true;
        $settings['pdf_show_logo'] = $settings['pdf_show_logo'] ?? true;
        $settings['pdf_invoice_template'] = $settings['pdf_invoice_template'] ?? 'classic';
        $settings['pdf_quote_template'] = $settings['pdf_quote_template'] ?? 'classic';
        $settings['pdf_paper'] = $settings['pdf_paper'] ?? 'A4';

        return $settings;
    }

    protected function resolveInvoiceBranding(array $settings, User $user): array
    {
        $tenant = $user->tenant()->first();

        return [
            'theme' => $settings['pdf_theme'] ?? 'ocean',
            'show_logo' => filter_var($settings['pdf_show_logo'] ?? true, FILTER_VALIDATE_BOOL),
            'show_footer' => filter_var($settings['pdf_show_footer'] ?? true, FILTER_VALIDATE_BOOL),
            'footer_text' => $settings['pdf_footer'] ?? '',
            'legal_mentions' => $settings['pdf_legal_mentions'] ?? '',
            'logo_path' => $this->resolveInvoiceLogoPath($settings, $tenant?->logo),
        ];
    }

    protected function resolveInvoiceLogoPath(array $settings, ?string $tenantLogo): ?string
    {
        $logoSetting = $settings['pdf_logo'] ?? null;
        if (!empty($logoSetting)) {
            $fromStorage = storage_path('app/public/' . ltrim((string) $logoSetting, '/'));
            if (is_file($fromStorage)) {
                return $fromStorage;
            }
        }

        if (empty($tenantLogo)) {
            return null;
        }

        $publicLogo = public_path(ltrim((string) $tenantLogo, '/'));
        if (is_file($publicLogo)) {
            return $publicLogo;
        }

        if (str_starts_with((string) $tenantLogo, 'storage/')) {
            $storageLogo = storage_path('app/public/' . substr((string) $tenantLogo, 8));
            if (is_file($storageLogo)) {
                return $storageLogo;
            }
        }

        return null;
    }

    protected function tenantUsers(User $user): Collection
    {
        $tenantId = (int) $user->tenant_id;
        $query = User::query();

        if (\Illuminate\Support\Facades\Schema::hasTable('tenant_user_memberships')) {
            $query
                ->join('tenant_user_memberships as tum', function ($join) use ($tenantId): void {
                    $join->on('users.id', '=', 'tum.user_id')
                        ->where('tum.tenant_id', '=', $tenantId)
                        ->where('tum.status', '=', 'active');
                })
                ->select('users.*');
        } else {
            $query->where('users.tenant_id', $tenantId)->select('users.*');
        }

        return $query->get();
    }

    protected function relativeWorkspacePath(TenantDataExport $export): string
    {
        return (string) data_get($export->meta, 'relative_workspace', '');
    }

    protected function workspacePath(TenantDataExport $export): string
    {
        return (string) ($export->workspace_path ?: storage_path('app/' . $this->relativeWorkspacePath($export)));
    }

    protected function appendLog(TenantDataExport $export, string $key, string $label, string $status, string $message): void
    {
        $meta = (array) ($export->meta ?? []);
        $logs = collect((array) ($meta['logs'] ?? []))
            ->push([
                'key' => $key,
                'label' => $label,
                'status' => $status,
                'message' => $message,
                'at' => now()->toIso8601String(),
            ])
            ->values()
            ->all();
        $meta['logs'] = $logs;
        $export->forceFill(['meta' => $meta])->save();
    }

    protected function appendWarnings(TenantDataExport $export, array $warnings): void
    {
        if ($warnings === []) {
            return;
        }

        $meta = (array) ($export->meta ?? []);
        $meta['warnings'] = collect((array) ($meta['warnings'] ?? []))
            ->merge($warnings)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $export->forceFill(['meta' => $meta])->save();
    }

    protected function recordGeneratedFiles(TenantDataExport $export, array $files): void
    {
        if ($files === []) {
            return;
        }

        $meta = (array) ($export->meta ?? []);
        $meta['generated_files'] = collect((array) ($meta['generated_files'] ?? []))
            ->merge($files)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $export->forceFill(['meta' => $meta])->save();
    }

    protected function writeJson(TenantDataExport $export, string $relativePath, mixed $payload): void
    {
        $absolutePath = $this->workspacePath($export) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
