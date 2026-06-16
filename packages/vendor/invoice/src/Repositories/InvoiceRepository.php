<?php

namespace Vendor\Invoice\Repositories;

use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Models\Quote;
use Illuminate\Support\Facades\Cache;

class InvoiceRepository
{
    public function findInvoice(int $id): ?Invoice
    {
        $tenantId = auth()->user()->tenant_id;
        return Cache::remember("invoice_{$tenantId}_{$id}", config('invoice.cache.ttl'), function () use ($id) {
            return Invoice::with(['client','items','payments'])->find($id);
        });
    }

    public function findQuote(int $id): ?Quote
    {
        $tenantId = auth()->user()->tenant_id;
        return Cache::remember("quote_{$tenantId}_{$id}", config('invoice.cache.ttl'), function () use ($id) {
            return Quote::with(['client','items'])->find($id);
        });
    }

    public function clearInvoiceCache(int $id): void
    {
        $tenantId = auth()->user()->tenant_id ?? 0;
        Cache::forget("invoice_{$tenantId}_{$id}");
    }

    public function clearQuoteCache(int $id): void
    {
        $tenantId = auth()->user()->tenant_id ?? 0;
        Cache::forget("quote_{$tenantId}_{$id}");
    }
}
