<?php

namespace Vendor\Invoice\Http\Controllers\Api;

use App\Services\DraftService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vendor\Invoice\Http\Requests\InvoiceRequest;
use Vendor\Invoice\Http\Requests\PaymentRequest;
use Vendor\Invoice\Http\Requests\QuoteRequest;
use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Models\Quote;
use Vendor\Invoice\Services\InvoiceService;

class InvoiceApiController extends Controller
{
    public function __construct(protected InvoiceService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->getFilteredInvoices($request->all()));
    }

    public function store(InvoiceRequest $request): JsonResponse
    {
        $invoice = $this->service->createInvoice($request->validated());
        app(DraftService::class)->forgetFromRequest($request);
        return response()->json(['success' => true, 'data' => $invoice], 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $invoice->load(['client', 'items', 'payments'])]);
    }

    public function update(InvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $invoice = $this->service->updateInvoice($invoice, $request->validated());
        app(DraftService::class)->forgetFromRequest($request);
        return response()->json(['success' => true, 'data' => $invoice]);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->service->deleteInvoice($invoice);
        return response()->json(['success' => true]);
    }

    public function send(Invoice $invoice): JsonResponse
    {
        $invoice->markAsSent();
        return response()->json(['success' => true]);
    }

    public function addPayment(PaymentRequest $request, Invoice $invoice): JsonResponse
    {
        $payment = $this->service->addPayment($invoice, $request->validated());
        return response()->json(['success' => true, 'data' => $payment], 201);
    }

    public function stats(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->service->getStats()]);
    }

    public function quotesIndex(Request $request): JsonResponse
    {
        return response()->json($this->service->getFilteredQuotes($request->all()));
    }

    public function quotesStore(QuoteRequest $request): JsonResponse
    {
        $quote = $this->service->createQuote($request->validated());
        app(DraftService::class)->forgetFromRequest($request);
        return response()->json(['success' => true, 'data' => $quote], 201);
    }

    public function quotesShow(Quote $quote): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $quote->load(['client', 'items', 'invoice'])]);
    }

    public function quotesUpdate(QuoteRequest $request, Quote $quote): JsonResponse
    {
        $quote = $this->service->updateQuote($quote, $request->validated());
        app(DraftService::class)->forgetFromRequest($request);
        return response()->json(['success' => true, 'data' => $quote]);
    }

    public function quotesDestroy(Quote $quote): JsonResponse
    {
        $this->service->deleteQuote($quote);
        return response()->json(['success' => true]);
    }

    public function quotesConvert(Quote $quote): JsonResponse
    {
        $invoice = $this->service->convertQuoteToInvoice($quote);
        return response()->json(['success' => true, 'data' => $invoice]);
    }
}
