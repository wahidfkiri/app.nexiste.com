<?php

namespace Vendor\Client\Repositories;

use Vendor\Client\Models\Client;
use Vendor\Client\Contracts\ClientRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class ClientRepository implements ClientRepositoryInterface
{
    protected $model;

    public function __construct(Client $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model
            ->byTenant(Auth::user()->tenant_id)
            ->latest()
            ->get();
    }

    public function getFiltered(array $filters, int $perPage = 15)
    {
        $query = $this->model
            ->byTenant(Auth::user()->tenant_id)
            ->filter($filters);
        
        if (isset($filters['sort_by']) && isset($filters['sort_order'])) {
            $query->orderBy($filters['sort_by'], $filters['sort_order']);
        } else {
            $query->latest();
        }
        
        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Client
    {
        return $this->model
            ->byTenant(Auth::user()->tenant_id)
            ->find($id);
    }

    public function create(array $data): Client
    {
        return $this->model->create($data);
    }

    public function update(Client $client, array $data): Client
    {
        $client->update($data);
        return $client->fresh();
    }

    public function delete(Client $client): bool
    {
        return $client->delete();
    }

    public function bulkDelete(array $ids): int
    {
        $count = 0;

        $this->model
            ->byTenant(Auth::user()->tenant_id)
            ->whereIn('id', $ids)
            ->get()
            ->each(function (Client $client) use (&$count): void {
                if ($client->delete()) {
                    $count++;
                }
            });

        return $count;
    }

    public function bulkStatusUpdate(array $ids, string $status): int
    {
        return $this->model
            ->byTenant(Auth::user()->tenant_id)
            ->whereIn('id', $ids)
            ->update(['status' => $status]);
    }

    public function count(): int
    {
        return $this->model
            ->byTenant(Auth::user()->tenant_id)
            ->count();
    }

    public function countByStatus(string $status): int
    {
        return $this->model
            ->byTenant(Auth::user()->tenant_id)
            ->where('status', $status)
            ->count();
    }

    public function countByType(): array
    {
        return $this->model
            ->byTenant(Auth::user()->tenant_id)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    public function countBySource(): array
    {
        return $this->model
            ->byTenant(Auth::user()->tenant_id)
            ->selectRaw('source, count(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();
    }

    public function sumRevenue(): float
    {
        return $this->model
            ->byTenant(Auth::user()->tenant_id)
            ->sum('revenue');
    }
}
