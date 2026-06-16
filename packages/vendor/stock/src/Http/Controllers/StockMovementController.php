<?php

namespace Vendor\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Vendor\Stock\Exports\StockMovementsExport;
use Vendor\Stock\Models\Article;
use Vendor\Stock\Services\StockMovementService;

class StockMovementController extends Controller
{
    public function __construct(protected StockMovementService $service) {}

    public function index(Request $request)
    {
        return view('stock::movements.index', [
            'articles' => Article::orderBy('name')->get(['id', 'name', 'sku']),
            'directions' => [
                'in' => trans('stock::stock.common.direction_in'),
                'out' => trans('stock::stock.common.direction_out'),
            ],
            'movementTypes' => config('stock.movement_types', []),
            'selectedArticleId' => $request->integer('article_id') ?: null,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $rows = $this->service->historyQuery($request->all())
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

    public function exportExcel()
    {
        return Excel::download(new StockMovementsExport(), 'historique_stock_' . date('Y-m-d') . '.xlsx');
    }
}
