<?php

namespace Vendor\Invoice\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Vendor\Invoice\Events\InvoiceCreated;
use Vendor\Invoice\Events\QuoteCreated;
use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Models\InvoiceItem;
use Vendor\Invoice\Models\Payment;
use Vendor\Invoice\Models\Quote;
use Vendor\Invoice\Models\QuoteItem;
use Vendor\CrmCore\Models\Tenant;
use Vendor\Invoice\Repositories\InvoiceRepository;

class InvoiceService
{
    public function __construct(protected InvoiceRepository $repo) {}

    /**
     * Résoudre la devise du tenant depuis les paramètres généraux (globaux).
     * Utilise la devise configurée dans le tenant, sinon la devise par défaut.
     */
    public static function tenantCurrency(?int $tenantId = null): string
    {
        $tenantId ??= auth()->user()?->tenant_id;
        if ($tenantId) {
            $currency = Tenant::whereKey($tenantId)->value('currency');
            if ($currency) {
                return strtoupper($currency);
            }
        }
        return strtoupper((string) config('invoice.default_currency', 'EUR'));
    }

    /**
     * Devise à appliquer au document : le choix de l'utilisateur (validé contre
     * la liste des devises configurées), sinon repli sur la devise du tenant.
     */
    protected function resolveCurrency($input, int $tenantId): string
    {
        $code = strtoupper(trim((string) ($input ?? '')));
        if ($code !== '' && array_key_exists($code, config('invoice.currencies', []))) {
            return $code;
        }

        return self::tenantCurrency($tenantId);
    }

    // Numerotation

    public function generateInvoiceNumber(int $tenantId): string
    {
        $prefix = config('invoice.numbering.invoice_prefix', 'FAC');
        $sep    = config('invoice.numbering.separator', '-');
        $digits = config('invoice.numbering.digits', 4);
        $year   = now()->year;

        $last = Invoice::withTrashed()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->max(DB::raw("CAST(SUBSTRING_INDEX(number, '{$sep}', -1) AS UNSIGNED)"));

        $next = max(1, (int) ($last ?? 0) + 1);

        return $this->nextAvailableDocumentNumber(
            Invoice::class,
            $tenantId,
            $prefix,
            $sep,
            $year,
            $digits,
            $next
        );
    }

    public function generateQuoteNumber(int $tenantId): string
    {
        $prefix = config('invoice.numbering.quote_prefix', 'DEV');
        $sep    = config('invoice.numbering.separator', '-');
        $digits = config('invoice.numbering.digits', 4);
        $year   = now()->year;

        $last = Quote::withTrashed()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->max(DB::raw("CAST(SUBSTRING_INDEX(number, '{$sep}', -1) AS UNSIGNED)"));

        $next = max(1, (int) ($last ?? 0) + 1);

        return $this->nextAvailableDocumentNumber(
            Quote::class,
            $tenantId,
            $prefix,
            $sep,
            $year,
            $digits,
            $next
        );
    }

    // Factures

    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $data['tenant_id'] = $data['tenant_id'] ?? auth()->user()->tenant_id;
            $data['currency'] = $this->resolveCurrency($data['currency'] ?? null, (int) $data['tenant_id']);
            $data['number']    = $this->generateInvoiceNumber($data['tenant_id']);
            $data['user_id']   = auth()->id();

            $items = $data['items'] ?? [];
            unset($data['items']);

            $invoice = Invoice::create($data);
            $this->syncItems($invoice, $items);
            $this->recalculate($invoice);
            $invoice = $invoice->fresh(['items', 'client']);
            DB::afterCommit(function () use ($invoice): void {
                event(new InvoiceCreated($invoice, [
                    'created_via' => request()?->expectsJson() ? 'api' : 'web',
                ]));
            });

            return $invoice;
        });
    }

    public function updateInvoice(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);
            // Devise choisie sur le document (repli sur la devise du tenant).
            $data['currency'] = $this->resolveCurrency($data['currency'] ?? $invoice->currency, (int) $invoice->tenant_id);

            $invoice->update($data);
            $this->syncItems($invoice, $items);
            $this->recalculate($invoice);

            return $invoice->fresh(['items', 'client']);
        });
    }

    public function deleteInvoice(Invoice $invoice): void
    {
        abort_if(in_array($invoice->status, ['paid']), 422, __('invoice::invoices.messages.invoice_cannot_delete_paid'));
        $invoice->delete();
    }    // Devis
    public function createQuote(array $data): Quote
    {
        return DB::transaction(function () use ($data) {
            $data['tenant_id'] = $data['tenant_id'] ?? auth()->user()->tenant_id;
            $data['currency'] = $this->resolveCurrency($data['currency'] ?? null, (int) $data['tenant_id']);
            $data['number']    = $this->generateQuoteNumber($data['tenant_id']);
            $data['user_id']   = auth()->id();

            $items = $data['items'] ?? [];
            unset($data['items']);

            $quote = Quote::create($data);
            $this->syncQuoteItems($quote, $items);
            $this->recalculateQuote($quote);
            $quote = $quote->fresh(['items', 'client']);
            DB::afterCommit(function () use ($quote): void {
                event(new QuoteCreated($quote, [
                    'created_via' => request()?->expectsJson() ? 'api' : 'web',
                ]));
            });

            return $quote;
        });
    }

    public function updateQuote(Quote $quote, array $data): Quote
    {
        return DB::transaction(function () use ($quote, $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);
            // Devise choisie sur le document (repli sur la devise du tenant).
            $data['currency'] = $this->resolveCurrency($data['currency'] ?? $quote->currency, (int) $quote->tenant_id);

            $quote->update($data);
            $this->syncQuoteItems($quote, $items);
            $this->recalculateQuote($quote);

            return $quote->fresh(['items', 'client']);
        });
    }

    public function deleteQuote(Quote $quote): void
    {
        abort_if($quote->is_converted, 422, __('invoice::invoices.messages.quote_already_converted'));
        $quote->delete();
    }

    /**
     * Convertir un devis en facture
     */
    public function convertQuoteToInvoice(Quote $quote): Invoice
    {
        abort_if($quote->is_converted, 422, __('invoice::invoices.messages.quote_already_converted_short'));
        abort_if(!$quote->canBeConvertedToInvoice(), 422, $quote->conversionBlockedReason());

        return DB::transaction(function () use ($quote) {
            $invoiceData = [
                'tenant_id'               => $quote->tenant_id,
                'client_id'               => $quote->client_id,
                'quote_id'                => $quote->id,
                'stock_order_id'          => $quote->stock_order_id,
                'currency'                => $quote->currency,
                'exchange_rate'           => $quote->exchange_rate,
                'issue_date'              => now()->toDateString(),
                'due_date'                => now()->addDays(config('invoice.payment_terms.30', 30))->toDateString(),
                'payment_terms'           => 30,
                'subtotal'                => $quote->subtotal,
                'discount_type'           => $quote->discount_type,
                'discount_value'          => $quote->discount_value,
                'discount_amount'         => $quote->discount_amount,
                'tax_rate'                => $quote->tax_rate,
                'tax_amount'              => $quote->tax_amount,
                'withholding_tax_rate'    => $quote->withholding_tax_rate,
                'withholding_tax_amount'  => $quote->withholding_tax_amount,
                'total'                   => $quote->total,
                'amount_due'              => $quote->total,
                'notes'                   => $quote->notes,
                'terms'                   => $quote->terms,
                'footer'                  => $quote->footer,
            ];

            $invoice = $this->createInvoice($invoiceData);

            // Copier les lignes du devis
            $quoteItems = $quote->items->map(fn($i) => [
                'invoice_id'      => $invoice->id,
                'position'        => $i->position,
                'description'     => $i->description,
                'article_id'      => $i->article_id,
                'reference'       => $i->reference,
                'quantity'        => $i->quantity,
                'unit'            => $i->unit,
                'unit_price'      => $i->unit_price,
                'discount_type'   => $i->discount_type,
                'discount_value'  => $i->discount_value,
                'discount_amount' => $i->discount_amount,
                'tax_rate'        => $i->tax_rate,
                'tax_amount'      => $i->tax_amount,
                'total'           => $i->total,
            ])->toArray();

            \Vendor\Invoice\Models\InvoiceItem::insert($quoteItems);
            $this->recalculate($invoice);

            $quote->update(['status' => 'accepted', 'converted_to_invoice_id' => $invoice->id, 'accepted_at' => now()]);

            return $invoice->fresh(['items', 'client']);
        });
    }

    // Paiements

    public function addPayment(Invoice $invoice, array $data): Payment
    {
        // Un paiement est libellé dans la devise de la facture.
        $rate = (float) ($invoice->exchange_rate ?: 1);
        $data['currency'] = $invoice->currency ?: self::tenantCurrency((int) $invoice->tenant_id);
        $data['exchange_rate'] = $rate;
        $data['tenant_id']  = $invoice->tenant_id;
        $data['invoice_id'] = $invoice->id;
        $data['user_id']    = auth()->id();
        $data['amount_base_currency'] = (float) $data['amount'] * $rate;

        return Payment::create($data);
    }

    public function deletePayment(Payment $payment): void
    {
        $payment->delete(); // L'observer met a jour la facture.
    }

    // Calculs

    public function recalculate(Invoice $invoice): void
    {
        $subtotal = (float) $invoice->items()->get()->sum(function ($item) {
            $line = $item->quantity * $item->unit_price;
            $disc = $item->discount_type === 'percent'
                ? $line * ($item->discount_value / 100)
                : (float) $item->discount_value;
            return $line - $disc;
        });

        $discountAmount = match($invoice->discount_type) {
            'percent' => $subtotal * ($invoice->discount_value / 100),
            'fixed'   => (float) $invoice->discount_value,
            default   => 0,
        };

        $taxable              = $subtotal - $discountAmount;
        $taxAmount            = $taxable * ($invoice->tax_rate / 100);
        $withholdingAmount    = $taxable * ($invoice->withholding_tax_rate / 100);
        $total                = $taxable + $taxAmount;
        $amountDue            = max(0, $total - $invoice->amount_paid);

        $invoice->updateQuietly([
            'subtotal'               => $subtotal,
            'discount_amount'        => $discountAmount,
            'tax_amount'             => $taxAmount,
            'withholding_tax_amount' => $withholdingAmount,
            'total'                  => $total,
            'amount_due'             => $amountDue,
        ]);
    }

    public function recalculateQuote(Quote $quote): void
    {
        $subtotal = (float) $quote->items()->get()->sum(function ($item) {
            $line = $item->quantity * $item->unit_price;
            $disc = $item->discount_type === 'percent'
                ? $line * ($item->discount_value / 100)
                : (float) $item->discount_value;
            return $line - $disc;
        });

        $discountAmount       = match($quote->discount_type) {
            'percent' => $subtotal * ($quote->discount_value / 100),
            'fixed'   => (float) $quote->discount_value,
            default   => 0,
        };
        $taxable              = $subtotal - $discountAmount;
        $taxAmount            = $taxable * ($quote->tax_rate / 100);
        $withholdingAmount    = $taxable * ($quote->withholding_tax_rate / 100);
        $total                = $taxable + $taxAmount;

        $quote->updateQuietly(compact('subtotal','taxAmount','withholdingAmount','total')
            + ['discount_amount' => $discountAmount, 'tax_amount' => $taxAmount, 'withholding_tax_amount' => $withholdingAmount]);
    }

    // Lignes

    private function syncItems(Invoice $invoice, array $items): void
    {
        $invoice->items()->delete();
        foreach ($items as $position => $item) {
            $line = $item['quantity'] * $item['unit_price'];
            $disc = match($item['discount_type'] ?? 'none') {
                'percent' => $line * (($item['discount_value'] ?? 0) / 100),
                'fixed'   => (float)($item['discount_value'] ?? 0),
                default   => 0,
            };
            $afterDisc = $line - $disc;
            $tax       = $afterDisc * (($item['tax_rate'] ?? 0) / 100);

            InvoiceItem::create(array_merge($item, [
                'invoice_id'      => $invoice->id,
                'position'        => $position,
                'discount_amount' => $disc,
                'tax_amount'      => $tax,
                'total'           => $afterDisc + $tax,
            ]));
        }
    }

    private function syncQuoteItems(Quote $quote, array $items): void
    {
        $quote->items()->delete();
        foreach ($items as $position => $item) {
            $line = $item['quantity'] * $item['unit_price'];
            $disc = match($item['discount_type'] ?? 'none') {
                'percent' => $line * (($item['discount_value'] ?? 0) / 100),
                'fixed'   => (float)($item['discount_value'] ?? 0),
                default   => 0,
            };
            $afterDisc = $line - $disc;
            $tax       = $afterDisc * (($item['tax_rate'] ?? 0) / 100);

            QuoteItem::create(array_merge($item, [
                'quote_id'        => $quote->id,
                'position'        => $position,
                'discount_amount' => $disc,
                'tax_amount'      => $tax,
                'total'           => $afterDisc + $tax,
            ]));
        }
    }

    // Statistiques

    public function getStats(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [
            'invoices' => [
                'total'      => Invoice::count(),
                'draft'      => Invoice::byStatus('draft')->count(),
                'sent'       => Invoice::byStatus('sent')->count(),
                'paid'       => Invoice::byStatus('paid')->count(),
                'overdue'    => Invoice::overdue()->count(),
                'total_ht'   => self::sumBase(Invoice::whereNotIn('status',['cancelled']), 'subtotal'),
                'total_ttc'  => self::sumBase(Invoice::whereNotIn('status',['cancelled']), 'total'),
                'paid_total' => self::sumBase(Invoice::byStatus('paid'), 'total'),
                'due_total'  => self::sumBase(Invoice::whereNotIn('status',['paid','cancelled']), 'amount_due'),
            ],
            'quotes' => [
                'total'    => Quote::count(),
                'draft'    => Quote::byStatus('draft')->count(),
                'sent'     => Quote::byStatus('sent')->count(),
                'accepted' => Quote::byStatus('accepted')->count(),
                'declined' => Quote::byStatus('declined')->count(),
                'expired'  => Quote::expired()->count(),
            ],
            'revenue' => [
                'month'     => self::sumBase(Invoice::byStatus('paid')->whereMonth('payment_date', now()->month), 'total'),
                'year'      => self::sumBase(Invoice::byStatus('paid')->whereYear('payment_date', now()->year), 'total'),
            ],
        ];
    }

    /**
     * Somme d'une colonne convertie en devise de base (montant × taux figé).
     */
    protected static function sumBase($query, string $column): float
    {
        return (float) $query
            ->selectRaw("COALESCE(SUM({$column} * COALESCE(exchange_rate, 1)), 0) as s")
            ->value('s');
    }

    public function getFilteredInvoices(array $filters): LengthAwarePaginator
    {
        return Invoice::with(['client','user'])
            ->filter($filters)
            ->paginate($filters['per_page'] ?? config('invoice.pagination.per_page'));
    }

    public function getFilteredQuotes(array $filters): LengthAwarePaginator
    {
        return Quote::with(['client','user'])
            ->filter($filters)
            ->paginate($filters['per_page'] ?? config('invoice.pagination.per_page'));
    }

    // Conversion de devise

    public function convertAmount(float $amount, string $from, string $to, ?float $rate = null): float
    {
        if ($from === $to) return $amount;
        if ($rate)         return $amount * $rate;

        // Recuperer les taux depuis la configuration ou la base.
        $fromRate = \Vendor\Invoice\Models\Currency::where('code', $from)->value('exchange_rate') ?? 1;
        $toRate   = \Vendor\Invoice\Models\Currency::where('code', $to)->value('exchange_rate') ?? 1;

        return $amount / $fromRate * $toRate;
    }

    protected function nextAvailableDocumentNumber(
        string $modelClass,
        int $tenantId,
        string $prefix,
        string $separator,
        int $year,
        int $digits,
        int $startAt
    ): string {
        $current = max(1, $startAt);

        for ($guard = 0; $guard < 2000; $guard++, $current++) {
            $candidate = "{$prefix}{$separator}{$year}{$separator}" . str_pad($current, $digits, '0', STR_PAD_LEFT);

            $exists = $modelClass::withTrashed()
                ->withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->where('number', $candidate)
                ->exists();

            if (!$exists) {
                return $candidate;
            }
        }

        throw new \RuntimeException(__('invoice::invoices.messages.document_number_generation_failed'));
    }
}
