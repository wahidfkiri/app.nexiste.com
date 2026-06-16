<?php

namespace Vendor\Client\Contracts;

use Vendor\Client\Models\Client;

interface ClientRepositoryInterface
{
    /**
     * Récupérer tous les clients
     */
    public function getAll();

    /**
     * Récupérer les clients filtrés
     */
    public function getFiltered(array $filters, int $perPage = 15);

    /**
     * Trouver un client par ID
     */
    public function findById(int $id): ?Client;

    /**
     * Créer un client
     */
    public function create(array $data): Client;

    /**
     * Mettre à jour un client
     */
    public function update(Client $client, array $data): Client;

    /**
     * Supprimer un client
     */
    public function delete(Client $client): bool;

    /**
     * Suppression massive
     */
    public function bulkDelete(array $ids): int;

    /**
     * Mise à jour massive du statut
     */
    public function bulkStatusUpdate(array $ids, string $status): int;

    /**
     * Compter tous les clients
     */
    public function count(): int;

    /**
     * Compter par statut
     */
    public function countByStatus(string $status): int;

    /**
     * Compter par type
     */
    public function countByType(): array;

    /**
     * Compter par source
     */
    public function countBySource(): array;

    /**
     * Somme du chiffre d'affaires
     */
    public function sumRevenue(): float;
}