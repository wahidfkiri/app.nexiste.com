<?php

namespace Vendor\Client\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Vendor\Client\Events\ClientCreated;
use Vendor\Client\Models\Client;
use Vendor\Client\Repositories\ClientRepository;

class ClientService
{
    public function __construct(protected ClientRepository $repository) {}

    /* ------------------------------------------------------------------ */
    /*  CRUD                                                               */
    /* ------------------------------------------------------------------ */

    public function create(array $data): Client
    {
        DB::beginTransaction();
        try {
            $client = $this->repository->create($data);
            $client = $client->fresh(['user:id,name,email', 'assignedTo:id,name,email']);
            DB::afterCommit(function () use ($client): void {
                event(new ClientCreated($client, [
                    'created_via' => request()?->expectsJson() ? 'api' : 'web',
                ]));
            });
            DB::commit();
            return $client;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ClientService::create', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        }
    }

    public function update(Client $client, array $data): Client
    {
        DB::beginTransaction();
        try {
            $client = $this->repository->update($client, $data);
            DB::commit();
            return $client;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ClientService::update', ['id' => $client->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(Client $client): bool
    {
        DB::beginTransaction();
        try {
            $result = $this->repository->delete($client);
            DB::commit();
            return $result;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ClientService::delete', ['id' => $client->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /* ------------------------------------------------------------------ */
    /*  BULK OPERATIONS                                                    */
    /* ------------------------------------------------------------------ */

    public function bulkDelete(array $ids): int
    {
        DB::beginTransaction();
        try {
            $count = $this->repository->bulkDelete($ids);
            DB::commit();
            return $count;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ClientService::bulkDelete', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function bulkStatusUpdate(array $ids, string $status): int
    {
        DB::beginTransaction();
        try {
            $count = $this->repository->bulkStatusUpdate($ids, $status);
            DB::commit();
            return $count;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ClientService::bulkStatusUpdate', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /* ------------------------------------------------------------------ */
    /*  QUERIES                                                            */
    /* ------------------------------------------------------------------ */

    public function getAllClients()
    {
        return $this->repository->getAll();
    }

    public function getFilteredClients(array $filters): LengthAwarePaginator
    {
        $perPage = min((int)($filters['per_page'] ?? config('client.pagination.per_page', 15)), 100);
        return $this->repository->getFiltered($filters, $perPage);
    }

    public function getStats(): array
    {
        return [
            'total'         => $this->repository->count(),
            'active'        => $this->repository->countByStatus('actif'),
            'inactive'      => $this->repository->countByStatus('inactif'),
            'pending'       => $this->repository->countByStatus('en_attente'),
            'revenue_total' => $this->repository->sumRevenue(),
            'by_type'       => $this->repository->countByType(),
            'by_source'     => $this->repository->countBySource(),
        ];
    }
}
