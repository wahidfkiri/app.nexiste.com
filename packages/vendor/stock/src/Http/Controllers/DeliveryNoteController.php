<?php

namespace Vendor\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;
use Vendor\Automation\Services\AutomationSuggestionPresenter;
use Vendor\Client\Models\Client;
use Vendor\Stock\Exports\DeliveryNotesExport;
use Vendor\Stock\Http\Requests\DeliveryNoteRequest;
use Vendor\Stock\Models\Article;
use Vendor\Stock\Models\DeliveryNote;
use Vendor\Stock\Models\Order;
use Vendor\Stock\Models\Supplier;
use Vendor\Stock\Services\DeliveryNoteService;

class DeliveryNoteController extends Controller
{
    public function __construct(protected DeliveryNoteService $service) {}

    public function index()
    {
        return view('stock::delivery-notes.index', [
            'types' => trans('stock::stock.labels.delivery_note_types'),
            'statuses' => config('stock.delivery_note_statuses', []),
        ]);
    }

    public function create()
    {
        return view('stock::delivery-notes.create', [
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'clients' => Client::orderBy('company_name')->get(['id', 'company_name']),
            'orders' => Order::with('supplier')->orderByDesc('created_at')->limit(100)->get(['id', 'supplier_id', 'number', 'status', 'reference']),
            'articles' => Article::query()->withCurrentStock()->where('status', 'active')->orderBy('name')->get(['stock_articles.id', 'name', 'sku', 'unit']),
            'types' => trans('stock::stock.labels.delivery_note_types'),
        ]);
    }

    public function store(DeliveryNoteRequest $request): JsonResponse
    {
        try {
            $note = $this->service->create($request->validated());
            $automation = app(AutomationSuggestionPresenter::class)->buildPromptForSource(
                'delivery_note_created',
                $note::class,
                $note->getKey(),
                (int) $note->tenant_id
            );

            return response()->json([
                'success' => true,
                'message' => trans('stock::stock.messages.delivery_note_created'),
                'redirect' => route('stock.delivery-notes.show', $note),
                'automation' => $automation,
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function show(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['supplier', 'client', 'order', 'invoice', 'items.article', 'movements.article', 'creator', 'validator', 'canceller']);
        return view('stock::delivery-notes.show', compact('deliveryNote'));
    }

    public function edit(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load('items.article');

        return view('stock::delivery-notes.edit', [
            'deliveryNote' => $deliveryNote,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'clients' => Client::orderBy('company_name')->get(['id', 'company_name']),
            'orders' => Order::with('supplier')->orderByDesc('created_at')->limit(100)->get(['id', 'supplier_id', 'number', 'status', 'reference']),
            'articles' => Article::query()->withCurrentStock()->where('status', 'active')->orderBy('name')->get(['stock_articles.id', 'name', 'sku', 'unit']),
            'types' => trans('stock::stock.labels.delivery_note_types'),
        ]);
    }

    public function update(DeliveryNoteRequest $request, DeliveryNote $deliveryNote): JsonResponse
    {
        try {
            $this->service->update($deliveryNote, $request->validated());

            return response()->json([
                'success' => true,
                'message' => trans('stock::stock.messages.delivery_note_updated'),
                'redirect' => route('stock.delivery-notes.show', $deliveryNote),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(DeliveryNote $deliveryNote): JsonResponse
    {
        try {
            $this->service->delete($deliveryNote);
            return response()->json(['success' => true, 'message' => trans('stock::stock.messages.delivery_note_deleted')]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function data(Request $request): JsonResponse
    {
        $rows = DeliveryNote::query()
            ->with(['supplier', 'client', 'order', 'items'])
            ->search($request->string('search')->toString())
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('issue_date', '>=', $request->string('date_from')->toString()))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('issue_date', '<=', $request->string('date_to')->toString()))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', config('stock.pagination.per_page')));

        return response()->json([
            'data' => $rows->items(),
            'current_page' => $rows->currentPage(),
            'last_page' => $rows->lastPage(),
            'per_page' => $rows->perPage(),
            'total' => $rows->total(),
            'from' => $rows->firstItem(),
            'to' => $rows->lastItem(),
        ]);
    }

    public function validateNote(DeliveryNote $deliveryNote): JsonResponse
    {
        try {
            $deliveryNote = $this->service->validate($deliveryNote);
            $automation = app(AutomationSuggestionPresenter::class)->buildPromptForSource(
                'delivery_note_validated',
                $deliveryNote::class,
                $deliveryNote->getKey(),
                (int) $deliveryNote->tenant_id
            );

            $lowStockArticle = $deliveryNote->items
                ->pluck('article')
                ->filter(fn ($article) => $article && $article->is_low_stock)
                ->first();

            if ($lowStockArticle) {
                $lowStockPrompt = app(AutomationSuggestionPresenter::class)->buildPromptForSource(
                    'stock_low_threshold_reached',
                    $lowStockArticle::class,
                    $lowStockArticle->getKey(),
                    (int) $deliveryNote->tenant_id
                );

                if (!empty($lowStockPrompt['count'])) {
                    $automation['suggestions'] = array_values(array_merge(
                        $automation['suggestions'] ?? [],
                        $lowStockPrompt['suggestions'] ?? []
                    ));
                    $automation['count'] = count($automation['suggestions']);
                    $automation['pending_count'] = $automation['count'];
                    $automation['should_prompt'] = $automation['count'] > 0;
                    $automation['subtitle'] = trans('stock::stock.automation.low_stock_delivery_note_subtitle');
                }
            }

            return response()->json([
                'success' => true,
                'message' => trans('stock::stock.messages.delivery_note_validated'),
                'redirect' => route('stock.delivery-notes.show', $deliveryNote),
                'automation' => $automation,
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function cancel(DeliveryNote $deliveryNote): JsonResponse
    {
        try {
            $deliveryNote = $this->service->cancel($deliveryNote);
            return response()->json([
                'success' => true,
                'message' => trans('stock::stock.messages.delivery_note_cancelled'),
                'redirect' => route('stock.delivery-notes.show', $deliveryNote),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function downloadPdf(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['supplier', 'client', 'order', 'invoice', 'items.article', 'creator', 'validator']);

        $pdf = app('dompdf.wrapper')
            ->loadView('stock::delivery-notes.pdf', compact('deliveryNote'))
            ->setPaper('A4');

        return $pdf->download(sprintf('bon-livraison-%s.pdf', $deliveryNote->number));
    }

    public function exportExcel()
    {
        return Excel::download(new DeliveryNotesExport(), 'bons_livraison_' . date('Y-m-d') . '.xlsx');
    }
}
