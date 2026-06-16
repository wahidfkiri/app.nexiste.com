<?php

namespace Vendor\Invoice\Observers;

use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Repositories\InvoiceRepository;

class InvoiceObserver
{
    public function __construct(protected InvoiceRepository $repo) {}

    public function created(Invoice $invoice): void
    {
        \Log::channel('daily')->info("[Invoice] Créée #{$invoice->number}", [
            'tenant_id' => $invoice->tenant_id,
            'client_id' => $invoice->client_id,
            'total'     => $invoice->total,
        ]);
    }

    public function updated(Invoice $invoice): void
    {
        $this->repo->clearInvoiceCache($invoice->id);

        // Auto-passer en "overdue" si échéance dépassée et non payée
        if ($invoice->due_date?->isPast()
            && !in_array($invoice->status, ['paid','cancelled','refunded','overdue'])
        ) {
            $invoice->updateQuietly(['status' => 'overdue']);
        }
    }

    public function deleted(Invoice $invoice): void
    {
        $this->repo->clearInvoiceCache($invoice->id);
        \Log::channel('daily')->info("[Invoice] Supprimée #{$invoice->number}");
    }
}
