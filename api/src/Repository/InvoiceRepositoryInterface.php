<?php

namespace App\Repository;

interface InvoiceRepositoryInterface
{
    public function findById(int $id): ?array;
    public function findAll(): array;
    public function save(array $data): int; // Retourne l'ID
    public function delete(int $id): bool;
}
