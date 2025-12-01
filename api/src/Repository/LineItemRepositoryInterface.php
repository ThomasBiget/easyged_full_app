<?php

namespace App\Repository;

use App\Models\LineItem;

interface LineItemRepositoryInterface
{
    public function create(LineItem $lineItem): int;

    public function findByInvoiceId(int $invoiceId): array;

    public function deleteByInvoiceId(int $invoiceId): bool;
}
