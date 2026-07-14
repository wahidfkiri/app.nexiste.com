<?php
namespace Vendor\Invoice\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Services\DraftService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Models\Quote;
use Vendor\Invoice\Models\Payment;
use Vendor\Invoice\Http\Requests\InvoiceRequest;
use Vendor\Invoice\Http\Requests\QuoteRequest;
use Vendor\Invoice\Http\Requests\PaymentRequest;
use Vendor\Invoice\Services\InvoiceService;
use Vendor\Invoice\Exports\InvoicesExport;
use Vendor\Invoice\Exports\QuotesExport;
use Vendor\Invoice\Exports\PaymentsExport;
use Vendor\Invoice\Imports\InvoicesImport;
use Vendor\CrmCore\Models\TenantSetting;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;
use Vendor\Automation\Services\AutomationSuggestionPresenter;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $service) {}

    /* Invoices CRUD */

    public function index()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);

        return view('invoice::invoices.index', [
            'statuses'       => config('invoice.invoice_statuses'),
            'currencies'     => config('invoice.currencies'),
            'payment_methods'=> config('invoice.payment_methods'),
            'marketplaceSuggestions' => array_values(array_filter([
                $this->makeMarketplaceSuggestion(
                    $tenantId,
                    'clients',
                    'Clients',
                    __('invoice::invoices.marketplace.clients_description')
                ),
                $this->makeMarketplaceSuggestion(
                    $tenantId,
                    'stock',
                    'Stock',
                    __('invoice::invoices.marketplace.stock_description')
                ),
            ])),
        ]);
    }

    public function create()
    {
        return view('invoice::invoices.create', [
            'currencies'          => config('invoice.currencies'),
            'payment_terms'       => config('invoice.payment_terms'),
            'payment_methods'     => config('invoice.payment_methods'),
            'tax_rates'           => config('invoice.tax.rates'),
            'withholding_rates'   => config('invoice.withholding_tax.rates'),
            'discount_types'      => config('invoice.discount.types'),
        ]);
    }

    public function store(InvoiceRequest $request): JsonResponse
    {
        try {
            $data              = $request->validated();
            $data['tenant_id'] = auth()->user()->tenant_id;

            $invoice = $this->service->createInvoice($data);
            app(DraftService::class)->forgetFromRequest($request);

            return response()->json([
                'success'  => true,
                'message'  => __('invoice::invoices.messages.invoice_created'),
                'data'     => $invoice,
                'automation' => app(AutomationSuggestionPresenter::class)->buildPromptForSource(
                    'invoice_created',
                    $invoice::class,
                    $invoice->getKey(),
                    (int) $invoice->tenant_id
                ),
                'redirect' => route('invoices.show', $invoice),
            ], 201);

        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client','items.article','payments','user']);
        return view('invoice::invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        abort_if($invoice->status === 'paid', 403, __('invoice::invoices.messages.invoice_cannot_edit_paid'));
        $invoice->load(['client','items.article']);
        return view('invoice::invoices.edit', [
            'invoice'           => $invoice,
            'currencies'        => config('invoice.currencies'),
            'payment_terms'     => config('invoice.payment_terms'),
            'payment_methods'   => config('invoice.payment_methods'),
            'tax_rates'         => config('invoice.tax.rates'),
            'withholding_rates' => config('invoice.withholding_tax.rates'),
            'discount_types'    => config('invoice.discount.types'),
        ]);
    }

    public function update(InvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        try {
            $invoice = $this->service->updateInvoice($invoice, $request->validated());
            app(DraftService::class)->forgetFromRequest($request);
            return response()->json([
                'success'  => true,
                'message'  => __('invoice::invoices.messages.invoice_updated'),
                'data'     => $invoice,
                'redirect' => route('invoices.show', $invoice),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        try {
            $this->service->deleteInvoice($invoice);
            return response()->json(['success' => true, 'message' => __('invoice::invoices.messages.invoice_deleted')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* Ajax data / stats */

    public function getData(Request $request): JsonResponse
    {
        $filters = $request->all();
        if (!isset($filters['sort']) && isset($filters['sort_by'])) {
            $filters['sort'] = $filters['sort_by'];
        }
        if (!isset($filters['order']) && isset($filters['sort_dir'])) {
            $filters['order'] = $filters['sort_dir'];
        }

        $invoices = $this->service->getFilteredInvoices($filters);
        return response()->json([
            'data'         => $invoices->items(),
            'current_page' => $invoices->currentPage(),
            'last_page'    => $invoices->lastPage(),
            'per_page'     => $invoices->perPage(),
            'total'        => $invoices->total(),
            'from'         => $invoices->firstItem(),
            'to'           => $invoices->lastItem(),
        ]);
    }

    public function getStats(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->service->getStats()]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:invoices,id',
        ]);

        $count = 0;
        foreach (Invoice::whereIn('id', $request->input('ids', []))->get() as $invoice) {
            try {
                $this->service->deleteInvoice($invoice);
                $count++;
            } catch (Throwable) {
                // ignored, keep deleting other draft/sent invoices
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('invoice::invoices.messages.bulk_invoices_deleted', ['count' => $count]),
        ]);
    }

    public function bulkSend(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:invoices,id',
        ]);

        $count = Invoice::whereIn('id', $request->input('ids', []))
            ->whereIn('status', ['draft'])
            ->get()
            ->tap(function ($invoices) {
                foreach ($invoices as $invoice) {
                    $invoice->markAsSent();
                }
            })->count();

        return response()->json([
            'success' => true,
            'message' => __('invoice::invoices.messages.bulk_invoices_sent', ['count' => $count]),
        ]);
    }

    /* Actions metier */

    public function send(Invoice $invoice): JsonResponse
    {
        try {
            $invoice->markAsSent();
            // TODO: dispatch SendInvoiceEmail job
            return response()->json(['success' => true, 'message' => __('invoice::invoices.messages.invoice_sent')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function duplicate(Invoice $invoice): JsonResponse
    {
        try {
            $data = $invoice->only([
                'client_id','currency','exchange_rate','payment_terms',
                'discount_type','discount_value','tax_rate','withholding_tax_rate',
                'notes','terms','footer',
            ]);
            $data['issue_date'] = now()->toDateString();
            $data['due_date']   = now()->addDays($invoice->payment_terms)->toDateString();
            $data['items']      = $invoice->items->toArray();
            $data['tenant_id']  = $invoice->tenant_id;

            $newInvoice = $this->service->createInvoice($data);
            return response()->json([
                'success'  => true,
                'message'  => __('invoice::invoices.messages.invoice_duplicated'),
                'redirect' => route('invoices.edit', $newInvoice),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* ================================================================
       PAIEMENTS
    ================================================================ */

    public function addPayment(PaymentRequest $request, Invoice $invoice): JsonResponse
    {
        try {
            $data = $request->validated();
            if ($request->hasFile('attachment')) {
                $data['attachment'] = $request->file('attachment')->store('payments', 'public');
            }

            $payment = $this->service->addPayment($invoice, $data);
            return response()->json(['success' => true, 'message' => __('invoice::invoices.messages.payment_created'), 'data' => $payment], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deletePayment(Payment $payment): JsonResponse
    {
        try {
            if ($payment->attachment) {
                Storage::disk('public')->delete($payment->attachment);
            }
            $this->service->deletePayment($payment);
            return response()->json(['success' => true, 'message' => __('invoice::invoices.messages.payment_deleted')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /* Exports / import */

    public function exportCsv()
    {
        return Excel::download(new InvoicesExport, 'factures_' . date('Y-m-d') . '.csv');
    }

    public function exportExcel()
    {
        return Excel::download(new InvoicesExport, 'factures_' . date('Y-m-d') . '.xlsx');
    }

    public function exportPdf()
    {
        $invoices = Invoice::with('client')->filter([])->get();
        $pdf = app('dompdf.wrapper')->loadView('invoice::exports.pdf', compact('invoices'));
        return $pdf->download('factures_' . date('Y-m-d') . '.pdf');
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['client','items','payments','tenant']);
        $settings = $this->getSettings();
        $branding = $this->resolvePdfBranding($settings, $invoice->tenant);
        $signature = [
            'enabled' => filter_var($settings['signature_enabled'] ?? false, FILTER_VALIDATE_BOOL),
            'data' => $settings['signature_data'] ?? null,
            'name' => $settings['signer_name'] ?? null,
            'title' => $settings['signer_title'] ?? null,
            'show_on_invoice' => filter_var($settings['signature_on_invoice'] ?? true, FILTER_VALIDATE_BOOL),
        ];
        $template = (string) ($settings['pdf_invoice_template'] ?? 'classic');
        $view = match ($template) {
            'modern'  => 'invoice::exports.pdf_invoice_modern',
            'minimal' => 'invoice::exports.pdf_invoice_minimal',
            default   => 'invoice::exports.pdf_invoice',
        };

        $pdf = app('dompdf.wrapper')
            ->loadView($view, compact('invoice', 'signature', 'branding'))
            ->setPaper('A4');
        return $pdf->download("facture-{$invoice->number}.pdf");
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv|max:10240']);
        try {
            Excel::import(new InvoicesImport, $request->file('file'));
            return response()->json(['success' => true, 'message' => __('invoice::invoices.messages.invoice_imported')]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('invoice::invoices.messages.import_error', ['message' => $e->getMessage()]),
            ], 500);
        }
    }

    /* Quotes CRUD */

    public function quotesIndex()
    {
        return view('invoice::quotes.index', [
            'statuses'   => config('invoice.quote_statuses'),
            'currencies' => config('invoice.currencies'),
        ]);
    }

    public function quotesCreate()
    {
        return view('invoice::quotes.create', [
            'currencies'        => config('invoice.currencies'),
            'tax_rates'         => config('invoice.tax.rates'),
            'withholding_rates' => config('invoice.withholding_tax.rates'),
            'discount_types'    => config('invoice.discount.types'),
        ]);
    }

    public function quotesStore(QuoteRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['tenant_id'] = auth()->user()->tenant_id;
            $quote = $this->service->createQuote($data);
            app(DraftService::class)->forgetFromRequest($request);
            return response()->json([
                'success'  => true,
                'message'  => __('invoice::invoices.messages.quote_created'),
                'data'     => $quote,
                'automation' => app(AutomationSuggestionPresenter::class)->buildPromptForSource(
                    'quote_created',
                    $quote::class,
                    $quote->getKey(),
                    (int) $quote->tenant_id
                ),
                'redirect' => route('invoices.quotes.show', $quote),
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function quotesShow(Quote $quote)
    {
        $quote->load(['client','items.article','user','invoice']);
        return view('invoice::quotes.show', compact('quote'));
    }

    public function quotesEdit(Quote $quote)
    {
        abort_if(in_array($quote->status, ['accepted','declined']), 403, __('invoice::invoices.messages.quote_cannot_edit'));
        $quote->load(['client','items.article']);
        return view('invoice::quotes.edit', [
            'quote'             => $quote,
            'currencies'        => config('invoice.currencies'),
            'tax_rates'         => config('invoice.tax.rates'),
            'withholding_rates' => config('invoice.withholding_tax.rates'),
            'discount_types'    => config('invoice.discount.types'),
        ]);
    }

    public function quotesUpdate(QuoteRequest $request, Quote $quote): JsonResponse
    {
        try {
            $quote = $this->service->updateQuote($quote, $request->validated());
            app(DraftService::class)->forgetFromRequest($request);
            return response()->json([
                'success'  => true,
                'message'  => __('invoice::invoices.messages.quote_updated'),
                'data'     => $quote,
                'redirect' => route('invoices.quotes.show', $quote),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function quotesDestroy(Quote $quote): JsonResponse
    {
        try {
            $this->service->deleteQuote($quote);
            return response()->json(['success' => true, 'message' => __('invoice::invoices.messages.quote_deleted')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function quotesConvert(Quote $quote): JsonResponse
    {
        try {
            $invoice = $this->service->convertQuoteToInvoice($quote);
            return response()->json([
                'success'  => true,
                'message'  => __('invoice::invoices.messages.quote_converted'),
                'redirect' => route('invoices.show', $invoice),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function quotesGetData(Request $request): JsonResponse
    {
        $filters = $request->all();
        if (!isset($filters['sort']) && isset($filters['sort_by'])) {
            $filters['sort'] = $filters['sort_by'];
        }
        if (!isset($filters['order']) && isset($filters['sort_dir'])) {
            $filters['order'] = $filters['sort_dir'];
        }

        $quotes = $this->service->getFilteredQuotes($filters);
        return response()->json([
            'data'         => $quotes->items(),
            'current_page' => $quotes->currentPage(),
            'last_page'    => $quotes->lastPage(),
            'per_page'     => $quotes->perPage(),
            'total'        => $quotes->total(),
        ]);
    }

    public function quotesDownloadPdf(Quote $quote)
    {
        $quote->load(['client','items','tenant']);
        $settings = $this->getSettings();
        $branding = $this->resolvePdfBranding($settings, $quote->tenant);
        $signature = [
            'enabled' => filter_var($settings['signature_enabled'] ?? false, FILTER_VALIDATE_BOOL),
            'data' => $settings['signature_data'] ?? null,
            'name' => $settings['signer_name'] ?? null,
            'title' => $settings['signer_title'] ?? null,
            'show_on_quote' => filter_var($settings['signature_on_quote'] ?? true, FILTER_VALIDATE_BOOL),
        ];
        $template = (string) ($settings['pdf_quote_template'] ?? 'classic');
        $view = match ($template) {
            'modern'  => 'invoice::exports.pdf_quote_modern',
            'minimal' => 'invoice::exports.pdf_quote_minimal',
            default   => 'invoice::exports.pdf_quote',
        };

        $pdf = app('dompdf.wrapper')
            ->loadView($view, compact('quote', 'signature', 'branding'))
            ->setPaper('A4');
        return $pdf->download("devis-{$quote->number}.pdf");
    }

    public function quotesExportCsv()
    {
        return Excel::download(new QuotesExport, 'devis_' . date('Y-m-d') . '.csv');
    }

    public function quotesExportExcel()
    {
        return Excel::download(new QuotesExport, 'devis_' . date('Y-m-d') . '.xlsx');
    }

    public function quotesExportPdf()
    {
        $quotes = Quote::with('client')->get();
        $pdf = app('dompdf.wrapper')->loadView('invoice::exports.pdf_quotes', compact('quotes'))->setPaper('A4');
        return $pdf->download('devis_' . date('Y-m-d') . '.pdf');
    }

    public function importTemplate()
    {
        return Excel::download(new \Vendor\Invoice\Exports\ImportTemplateExport, 'modele-import-factures.xlsx');
    }

    public function paymentsIndex()
    {
        return view('invoice::payments.index');
    }

    public function paymentsData(Request $request): JsonResponse
    {
        $filters = $request->all();
        $sort = $filters['sort_by'] ?? $filters['sort'] ?? 'payment_date';
        $order = $filters['sort_dir'] ?? $filters['order'] ?? 'desc';

        $payments = Payment::with(['invoice.client'])
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = $filters['search'];
                $q->where(function ($w) use ($term) {
                    $w->where('reference', 'like', "%{$term}%")
                        ->orWhereHas('invoice', fn ($inv) => $inv->where('number', 'like', "%{$term}%"))
                        ->orWhereHas('invoice.client', fn ($c) => $c->where('company_name', 'like', "%{$term}%"));
                });
            })
            ->when(!empty($filters['payment_method']), fn ($q) => $q->where('payment_method', $filters['payment_method']))
            ->when(!empty($filters['date_from']), fn ($q) => $q->whereDate('payment_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn ($q) => $q->whereDate('payment_date', '<=', $filters['date_to']))
            ->orderBy($sort, $order)
            ->paginate($filters['per_page'] ?? config('invoice.pagination.per_page'));

        return response()->json([
            'data' => $payments->items(),
            'current_page' => $payments->currentPage(),
            'last_page' => $payments->lastPage(),
            'per_page' => $payments->perPage(),
            'total' => $payments->total(),
            'from' => $payments->firstItem(),
            'to' => $payments->lastItem(),
        ]);
    }

    public function paymentsStats(): JsonResponse
    {
        $base = Payment::query();

        $data = [
            'total' => (float) $base->sum('amount'),
            'month' => (float) Payment::whereMonth('payment_date', now()->month)->whereYear('payment_date', now()->year)->sum('amount'),
            'count' => (int) Payment::count(),
            'transfer' => (float) Payment::where('payment_method', 'bank_transfer')->sum('amount'),
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function paymentsExportCsv()
    {
        return Excel::download(new PaymentsExport(), 'paiements_' . date('Y-m-d') . '.csv');
    }

    public function paymentsExportExcel()
    {
        return Excel::download(new PaymentsExport(), 'paiements_' . date('Y-m-d') . '.xlsx');
    }

    public function reportsIndex()
    {
        $stats = $this->service->getStats();
        $tenantId = auth()->user()->tenant_id;

        $year = (int) request('year', now()->year);

        $monthlyRevenue = [];
        $monthlyPaid = [];
        $monthlyCount = [];
        $monthlyOverdue = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyRevenue[$m] = (float) Invoice::whereYear('issue_date', $year)->whereMonth('issue_date', $m)->sum('total');
            $monthlyPaid[$m] = (float) Payment::whereYear('payment_date', $year)->whereMonth('payment_date', $m)->sum('amount');
            $monthlyCount[$m] = (int) Invoice::whereYear('issue_date', $year)->whereMonth('issue_date', $m)->count();
            $monthlyOverdue[$m] = (float) Invoice::whereYear('issue_date', $year)->whereMonth('issue_date', $m)->where('amount_due', '>', 0)->sum('amount_due');
        }

        $topClients = DB::table('invoices')
            ->join('clients', 'clients.id', '=', 'invoices.client_id')
            ->selectRaw('clients.company_name, count(invoices.id) as invoice_count, sum(invoices.total) as total_revenue')
            ->where('invoices.tenant_id', $tenantId)
            ->groupBy('clients.company_name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        $paymentMethods = Payment::selectRaw('payment_method, sum(amount) as total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        return view('invoice::reports.index', compact(
            'stats',
            'monthlyRevenue',
            'monthlyPaid',
            'monthlyCount',
            'monthlyOverdue',
            'topClients',
            'paymentMethods'
        ));
    }

    public function reportsExport(string $format)
    {
        if ($format === 'excel') {
            return Excel::download(new InvoicesExport(), 'rapport_facturation_' . date('Y-m-d') . '.xlsx');
        }

        if ($format === 'pdf') {
            $invoices = Invoice::with('client')->latest()->limit(200)->get();
            $pdf = app('dompdf.wrapper')->loadView('invoice::exports.pdf', compact('invoices'));
            return $pdf->download('rapport_facturation_' . date('Y-m-d') . '.pdf');
        }

        abort(404);
    }

    public function settingsIndex()
    {
        $settings = $this->getSettings();
        $tenant = auth()->user()->tenant;
        $currentCurrency = strtoupper((string) ($tenant->currency ?? config('invoice.default_currency', 'EUR')));
        $currencies = (array) config('onboarding.currencies', []);

        return view('invoice::invoices.settings', compact('settings', 'currentCurrency', 'currencies'));
    }

    public function settingsUpdate(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $booleanKeys = [
            'signature_enabled',
            'signature_on_invoice',
            'signature_on_quote',
            'pdf_show_bank',
            'pdf_show_footer',
            'pdf_show_logo',
            'pdf_watermark_draft',
        ];

        $payload = $request->except(['_token', '_method']);
        foreach ($booleanKeys as $boolKey) {
            $payload[$boolKey] = $request->boolean($boolKey);
        }

        // La devise est une préférence globale du tenant (partagée par tous les
        // modules), pas un réglage propre à la facturation : on l'écrit sur la
        // colonne tenant.currency pour la synchroniser avec les paramètres généraux.
        if (array_key_exists('tenant_currency', $payload)) {
            $requested = strtoupper((string) $payload['tenant_currency']);
            $allowed = array_map('strtoupper', array_keys((array) config('onboarding.currencies', [])));

            if ($requested !== '' && in_array($requested, $allowed, true)) {
                $tenant = auth()->user()->tenant;
                if ($tenant && $tenant->currency !== $requested) {
                    $tenant->update(['currency' => $requested]);
                }
            }

            unset($payload['tenant_currency']);
        }

        if ($request->boolean('pdf_logo_remove')) {
            $old = TenantSetting::where('tenant_id', $tenantId)->where('key', 'invoice.pdf_logo')->value('value');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            $payload['pdf_logo'] = null;
        }

        if ($request->hasFile('pdf_logo')) {
            $old = TenantSetting::where('tenant_id', $tenantId)->where('key', 'invoice.pdf_logo')->value('value');
            if ($old) {
                Storage::disk('public')->delete($old);
            }

            $payload['pdf_logo'] = $request->file('pdf_logo')->store("invoice/branding/tenant-{$tenantId}", 'public');
        }

        unset($payload['pdf_logo_remove']);

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }

            TenantSetting::updateOrCreate(
                ['tenant_id' => $tenantId, 'key' => "invoice.{$key}"],
                ['value' => (string) $value]
            );
        }

        return response()->json([
            'success' => true,
            'message' => __('invoice::invoices.messages.settings_updated'),
        ]);
    }

    protected function getSettings(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $rows = TenantSetting::where('tenant_id', $tenantId)
            ->where('key', 'like', 'invoice.%')
            ->get(['key', 'value']);

        $settings = [];
        foreach ($rows as $row) {
            $shortKey = str_replace('invoice.', '', $row->key);
            $value = $row->value;
            if (is_string($value) && (str_starts_with($value, '[') || str_starts_with($value, '{'))) {
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

    /**
     * Aperçu réel d'un modèle PDF (facture ou devis) avec des données d'exemple,
     * afin que le client visualise le vrai rendu avant de sélectionner un modèle.
     */
    public function previewTemplate(Request $request)
    {
        $type = $request->query('type') === 'quote' ? 'quote' : 'invoice';
        $template = in_array($request->query('template'), ['modern', 'minimal'], true)
            ? (string) $request->query('template')
            : 'classic';

        $settings = $this->getSettings();
        $tenant = auth()->user()->tenant;
        $branding = $this->resolvePdfBranding($settings, $tenant);
        $signature = [
            'enabled' => filter_var($settings['signature_enabled'] ?? false, FILTER_VALIDATE_BOOL),
            'data' => $settings['signature_data'] ?? null,
            'name' => $settings['signer_name'] ?? null,
            'title' => $settings['signer_title'] ?? null,
            'show_on_invoice' => filter_var($settings['signature_on_invoice'] ?? true, FILTER_VALIDATE_BOOL),
            'show_on_quote' => filter_var($settings['signature_on_quote'] ?? true, FILTER_VALIDATE_BOOL),
        ];
        $currency = InvoiceService::tenantCurrency($tenant?->id);

        if ($type === 'quote') {
            $quote = $this->sampleQuote($tenant, $currency);
            $view = match ($template) {
                'modern' => 'invoice::exports.pdf_quote_modern',
                'minimal' => 'invoice::exports.pdf_quote_minimal',
                default => 'invoice::exports.pdf_quote',
            };
            $pdf = app('dompdf.wrapper')->loadView($view, compact('quote', 'signature', 'branding'))->setPaper('A4');
            return $pdf->stream("apercu-devis-{$template}.pdf");
        }

        $invoice = $this->sampleInvoice($tenant, $currency);
        $view = match ($template) {
            'modern' => 'invoice::exports.pdf_invoice_modern',
            'minimal' => 'invoice::exports.pdf_invoice_minimal',
            default => 'invoice::exports.pdf_invoice',
        };
        $pdf = app('dompdf.wrapper')->loadView($view, compact('invoice', 'signature', 'branding'))->setPaper('A4');
        return $pdf->stream("apercu-facture-{$template}.pdf");
    }

    private function sampleClient(): \Vendor\Client\Models\Client
    {
        return (new \Vendor\Client\Models\Client())->forceFill([
            'company_name' => 'Société Exemple SARL',
            'contact_name' => 'Jean Dupont',
            'full_address' => '12 rue des Lilas, 75011 Paris',
            'email' => 'contact@exemple.fr',
            'vat_number' => 'FR12345678901',
        ]);
    }

    private function sampleInvoice(mixed $tenant, string $currency): Invoice
    {
        $invoice = (new Invoice())->forceFill([
            'number' => 'FAC-' . date('Y') . '-0001',
            'reference' => 'APERÇU',
            'status' => 'sent',
            'currency' => $currency,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'payment_method' => 'transfer',
            'tax_rate' => 20,
            'subtotal' => 1500, 'discount_amount' => 0, 'tax_amount' => 300,
            'withholding_tax_rate' => 0, 'withholding_tax_amount' => 0,
            'amount_paid' => 0, 'amount_due' => 1800, 'total' => 1800,
            'notes' => 'Merci de votre confiance.',
            'terms' => 'Paiement à 30 jours par virement bancaire.',
        ]);
        $invoice->setRelation('tenant', $tenant);
        $invoice->setRelation('client', $this->sampleClient());
        $invoice->setRelation('items', collect([
            (new \Vendor\Invoice\Models\InvoiceItem())->forceFill(['description' => 'Prestation de conseil', 'reference' => 'SRV-01', 'quantity' => 10, 'unit' => 'h', 'unit_price' => 120, 'discount_amount' => 0, 'tax_rate' => 20, 'total' => 1200]),
            (new \Vendor\Invoice\Models\InvoiceItem())->forceFill(['description' => 'Licence logicielle', 'reference' => 'LIC-02', 'quantity' => 1, 'unit' => 'u', 'unit_price' => 300, 'discount_amount' => 0, 'tax_rate' => 20, 'total' => 300]),
        ]));

        return $invoice;
    }

    private function sampleQuote(mixed $tenant, string $currency): Quote
    {
        $quote = (new Quote())->forceFill([
            'number' => 'DEV-' . date('Y') . '-0001',
            'reference' => 'APERÇU',
            'status' => 'sent',
            'currency' => $currency,
            'issue_date' => now(),
            'valid_until' => now()->addDays(30),
            'tax_rate' => 20,
            'subtotal' => 1500, 'discount_amount' => 0, 'tax_amount' => 300,
            'withholding_tax_rate' => 0, 'withholding_tax_amount' => 0,
            'total' => 1800,
            'notes' => 'Proposition commerciale valable 30 jours.',
            'terms' => 'Devis sans engagement.',
        ]);
        $quote->setRelation('tenant', $tenant);
        $quote->setRelation('client', $this->sampleClient());
        $quote->setRelation('items', collect([
            (new \Vendor\Invoice\Models\QuoteItem())->forceFill(['description' => 'Prestation de conseil', 'reference' => 'SRV-01', 'quantity' => 10, 'unit' => 'h', 'unit_price' => 120, 'discount_amount' => 0, 'tax_rate' => 20, 'total' => 1200]),
            (new \Vendor\Invoice\Models\QuoteItem())->forceFill(['description' => 'Licence logicielle', 'reference' => 'LIC-02', 'quantity' => 1, 'unit' => 'u', 'unit_price' => 300, 'discount_amount' => 0, 'tax_rate' => 20, 'total' => 300]),
        ]));

        return $quote;
    }

    protected function resolvePdfBranding(array $settings, mixed $tenant): array
    {
        return [
            'theme' => $settings['pdf_theme'] ?? 'ocean',
            'primary_color' => $this->normalizeHexColor($settings['pdf_primary_color'] ?? null),
            'show_logo' => filter_var($settings['pdf_show_logo'] ?? true, FILTER_VALIDATE_BOOL),
            'show_footer' => filter_var($settings['pdf_show_footer'] ?? true, FILTER_VALIDATE_BOOL),
            'footer_text' => $settings['pdf_footer'] ?? '',
            'legal_mentions' => $settings['pdf_legal_mentions'] ?? '',
            'logo_path' => $this->resolveLogoPath($settings, $tenant),
        ];
    }

    /**
     * Retourne le logo sous forme de data URI base64 : garantit son affichage
     * dans le PDF quelle que soit la configuration chroot de dompdf (le chemin
     * storage/ est souvent bloqué). Renvoie null si aucun logo valable.
     */
    protected function resolveLogoPath(array $settings, mixed $tenant): ?string
    {
        $file = $this->locateLogoFile($settings, $tenant);
        if (!$file || !is_file($file)) {
            return null;
        }

        $data = @file_get_contents($file);
        if ($data === false) {
            return null;
        }

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    private function locateLogoFile(array $settings, mixed $tenant): ?string
    {
        $logoSetting = $settings['pdf_logo'] ?? null;
        if (!empty($logoSetting)) {
            $fromStorage = storage_path('app/public/' . ltrim((string) $logoSetting, '/'));
            if (is_file($fromStorage)) {
                return $fromStorage;
            }
        }

        $tenantLogo = $tenant?->logo ?? null;
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

    private function normalizeHexColor(?string $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : null;
    }

    /* Devise Ajax */

    public function getExchangeRate(Request $request): JsonResponse
    {
        $defaultCurrency = \Vendor\Invoice\Services\InvoiceService::tenantCurrency();
        $from = strtoupper($request->string('from', $defaultCurrency));
        $to   = strtoupper($request->string('to', $defaultCurrency));

        $fromDef = config("invoice.currencies.{$from}");
        $toDef   = config("invoice.currencies.{$to}");

        if (!$fromDef || !$toDef) {
            return response()->json(['success' => false, 'message' => __('invoice::invoices.messages.unknown_currency')], 422);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'from'   => $from,
                'to'     => $to,
                'rate'   => 1.0, // Integrer une API de taux (OpenExchangeRates, Fixer.io...)
                'symbol' => $toDef['symbol'],
            ],
        ]);
    }

    private function makeMarketplaceSuggestion(int $tenantId, string $slug, string $fallbackName, string $description): ?array
    {
        if ($tenantId <= 0) {
            return null;
        }

        $extension = Extension::query()->where('slug', $slug)->first();
        if (!$extension) {
            return null;
        }

        $isActive = TenantExtension::query()
            ->where('tenant_id', $tenantId)
            ->where('extension_id', $extension->id)
            ->whereIn('status', ['active', 'trial'])
            ->exists();

        if ($isActive) {
            return null;
        }

        return [
            'slug' => $slug,
            'name' => (string) ($extension->name ?: $fallbackName),
            'description' => $description,
            'url' => route('marketplace.show', ['slug' => $slug]),
            'icon' => (string) ($extension->icon ?: 'fas fa-puzzle-piece'),
        ];
    }
}





