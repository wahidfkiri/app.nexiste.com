<?php

namespace Vendor\Invoice\Observers;

use Vendor\Invoice\Models\Quote;
use Vendor\Invoice\Repositories\InvoiceRepository;

class QuoteObserver
{
    public function __construct(protected InvoiceRepository $repo) {}

    public function updated(Quote $quote): void
    {
        $this->repo->clearQuoteCache($quote->id);

        // Auto-passer en "expired"
        if ($quote->valid_until?->isPast()
            && !in_array($quote->status, ['accepted','declined','expired'])
        ) {
            $quote->updateQuietly(['status' => 'expired']);
        }
    }

    public function deleted(Quote $quote): void
    {
        $this->repo->clearQuoteCache($quote->id);
    }
}
