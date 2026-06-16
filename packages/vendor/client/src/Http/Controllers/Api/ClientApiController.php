<?php

namespace Vendor\Client\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DraftService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Vendor\Client\Exports\ClientsExport;
use Vendor\Client\Http\Requests\ClientRequest;
use Vendor\Client\Http\Resources\ClientResource;
use Vendor\Client\Models\Client;
use Vendor\Client\Services\ClientService;

class ClientApiController extends Controller
{
    public function __construct(protected ClientService $clientService) {}

    public function index(Request $request)
    {
        $clients = $this->clientService->getFilteredClients($request->all());

        return ClientResource::collection($clients);
    }

    public function store(ClientRequest $request)
    {
        try {
            $data = $request->validated();
            $data['user_id'] = auth()->id();
            $data['tenant_id'] = auth()->user()->tenant_id;

            $client = $this->clientService->create($data);
            app(DraftService::class)->forgetFromRequest($request);

            return response()->json([
                'success' => true,
                'message' => __('client::clients.messages.created'),
                'data' => new ClientResource($client),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('client::clients.messages.create_error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    public function show(Client $client)
    {
        $this->authorize('view', $client);

        return new ClientResource($client);
    }

    public function update(ClientRequest $request, Client $client)
    {
        $this->authorize('update', $client);

        try {
            $client = $this->clientService->update($client, $request->validated());
            app(DraftService::class)->forgetFromRequest($request);

            return response()->json([
                'success' => true,
                'message' => __('client::clients.messages.updated'),
                'data' => new ClientResource($client),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('client::clients.messages.update_error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    public function destroy(Client $client)
    {
        $this->authorize('delete', $client);

        try {
            $this->clientService->delete($client);

            return response()->json([
                'success' => true,
                'message' => __('client::clients.messages.deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('client::clients.messages.delete_error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:clients,id',
        ]);

        try {
            $count = $this->clientService->bulkDelete($request->ids);

            return response()->json([
                'success' => true,
                'message' => __('client::clients.messages.bulk_deleted', ['count' => $count]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('client::clients.messages.bulk_delete_error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    public function bulkStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'status' => 'required|in:actif,inactif,en_attente,suspendu',
        ]);

        try {
            $count = $this->clientService->bulkStatusUpdate($request->ids, $request->status);

            return response()->json([
                'success' => true,
                'message' => __('client::clients.messages.bulk_updated', ['count' => $count]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('client::clients.messages.bulk_status_error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'xlsx');
        $filename = 'clients_' . date('Y-m-d') . '.' . $format;

        return Excel::download(new ClientsExport(), $filename);
    }

    public function search(Request $request)
    {
        $term = $request->get('q');
        $limit = $request->get('limit', 10);

        $clients = Client::search($term)
            ->byTenant(auth()->user()->tenant_id)
            ->limit($limit)
            ->get(['id', 'company_name', 'email', 'phone']);

        return response()->json([
            'success' => true,
            'data' => $clients,
        ]);
    }

    public function filter(Request $request)
    {
        $clients = $this->clientService->getFilteredClients($request->all());

        return ClientResource::collection($clients);
    }

    public function getStats()
    {
        $stats = $this->clientService->getStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
