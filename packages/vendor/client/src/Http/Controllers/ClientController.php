<?php

namespace Vendor\Client\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;
use Vendor\Automation\Services\AutomationSuggestionPresenter;
use Vendor\Client\Exports\ClientsExport;
use Vendor\Client\Http\Requests\ClientRequest;
use Vendor\Client\Imports\ClientsImport;
use Vendor\Client\Models\Client;
use Vendor\Client\Services\ClientService;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

class ClientController extends Controller
{
    public function __construct(protected ClientService $clientService) {}

    public function index()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);

        return view('client::index', [
            'types' => trans('client::clients.types'),
            'statuses' => trans('client::clients.statuses'),
            'sources' => trans('client::clients.sources'),
            'marketplaceSuggestions' => array_values(array_filter([
                $this->makeMarketplaceSuggestion(
                    $tenantId,
                    'invoice',
                    'Facturation',
                    trans('client::clients.marketplace.invoice_description')
                ),
                $this->makeMarketplaceSuggestion(
                    $tenantId,
                    'stock',
                    'Stock',
                    trans('client::clients.marketplace.stock_description')
                ),
            ])),
        ]);
    }

    public function create()
    {
        return view('client::create', [
            'types' => trans('client::clients.types'),
            'statuses' => trans('client::clients.statuses'),
            'sources' => trans('client::clients.sources'),
        ]);
    }

    public function store(ClientRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['user_id'] = auth()->id();
            $data['tenant_id'] = auth()->user()->tenant_id ?? null;

            $client = $this->clientService->create($data);
            app(DraftService::class)->forgetFromRequest($request);

            return response()->json([
                'success' => true,
                'message' => __('client::clients.messages.created'),
                'data' => $client,
                'automation' => app(AutomationSuggestionPresenter::class)->buildPromptForSource(
                    'client_created',
                    $client::class,
                    $client->getKey(),
                    (int) $client->tenant_id
                ),
                'redirect' => route('clients.show', $client),
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('client::clients.messages.create_error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    public function show(Client $client)
    {
        $stats = $this->clientService->getStats();

        return view('client::show', compact('client', 'stats'));
    }

    public function edit(Client $client)
    {
        return view('client::edit', [
            'client' => $client,
            'types' => trans('client::clients.types'),
            'statuses' => trans('client::clients.statuses'),
            'sources' => trans('client::clients.sources'),
        ]);
    }

    public function update(ClientRequest $request, Client $client): JsonResponse
    {
        try {
            $client = $this->clientService->update($client, $request->validated());
            app(DraftService::class)->forgetFromRequest($request);

            return response()->json([
                'success' => true,
                'message' => __('client::clients.messages.updated'),
                'data' => $client,
                'redirect' => route('clients.show', $client),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('client::clients.messages.update_error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    public function destroy(Client $client): JsonResponse
    {
        try {
            $this->clientService->delete($client);

            return response()->json([
                'success' => true,
                'message' => __('client::clients.messages.deleted'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('client::clients.messages.delete_error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    public function getData(Request $request): JsonResponse
    {
        $clients = $this->clientService->getFilteredClients($request->all());

        return response()->json([
            'data' => $clients->items(),
            'current_page' => $clients->currentPage(),
            'last_page' => $clients->lastPage(),
            'per_page' => $clients->perPage(),
            'total' => $clients->total(),
            'from' => $clients->firstItem(),
            'to' => $clients->lastItem(),
        ]);
    }

    public function getStats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->clientService->getStats(),
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:clients,id',
        ]);

        try {
            $count = $this->clientService->bulkDelete($request->ids);

            return response()->json([
                'success' => true,
                'message' => __('client::clients.messages.bulk_deleted', ['count' => $count]),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('client::clients.messages.bulk_delete_error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    public function bulkStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:clients,id',
            'status' => 'required|in:actif,inactif,en_attente,suspendu',
        ]);

        try {
            $count = $this->clientService->bulkStatusUpdate($request->ids, $request->status);

            return response()->json([
                'success' => true,
                'message' => __('client::clients.messages.bulk_updated', ['count' => $count]),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('client::clients.messages.bulk_status_error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    public function exportCsv()
    {
        return Excel::download(new ClientsExport(), 'clients_' . date('Y-m-d') . '.csv');
    }

    public function exportExcel()
    {
        return Excel::download(new ClientsExport(), 'clients_' . date('Y-m-d') . '.xlsx');
    }

    public function exportPdf()
    {
        $clients = $this->clientService->getAllClients();
        $pdf = app('dompdf.wrapper')->loadView('client::exports.pdf', compact('clients'));

        return $pdf->download('clients_' . date('Y-m-d') . '.pdf');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new ClientsImport(), $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => __('client::clients.messages.imported'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('client::clients.messages.import_error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        $headers = ['company_name', 'contact_name', 'email', 'phone', 'type', 'status', 'source', 'city', 'country'];
        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ['Acme Corp', 'Jean Dupont', 'jean@acme.com', '+33612345678', 'entreprise', 'actif', 'direct', 'Paris', 'France']);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_clients.csv"',
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = $request->string('q')->trim()->toString();
        $clients = Client::search($term)->limit(10)->get(['id', 'company_name', 'email', 'phone', 'currency']);

        return response()->json([
            'success' => true,
            'data' => $clients,
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
