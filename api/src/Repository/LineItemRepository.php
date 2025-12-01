<?php

namespace App\Repository;

use PDO;
use App\Models\LineItem;

class LineItemRepository implements LineItemRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(LineItem $lineItem): int
    {
        $sql = "
            INSERT INTO line_items 
            (invoice_id, description, quantity, unit_price, total_price, verified)
            VALUES 
            (:invoice_id, :description, :quantity, :unit_price, :total_price, :verified)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'invoice_id'  => $lineItem->invoice_id,
            'description' => $lineItem->description,
            'quantity'    => $lineItem->quantity,
            'unit_price'  => $lineItem->unit_price,
            'total_price' => $lineItem->total_price,
            'verified'    => (int) $lineItem->verified
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findByInvoiceId(int $invoiceId): array
    {
        $sql = "
            SELECT * 
            FROM line_items 
            WHERE invoice_id = :invoice_id 
            ORDER BY id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['invoice_id' => $invoiceId]);

        $items = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $item = new LineItem();
            $item->id = (int) $row['id'];
            $item->invoice_id = (int) $row['invoice_id'];
            $item->description = $row['description'];
            $item->quantity = (float) $row['quantity'];
            $item->unit_price = (float) $row['unit_price'];
            $item->total_price = (float) $row['total_price'];
            $item->verified = (bool) $row['verified'];

            $items[] = $item;
        }

        return $items;
    }

    public function deleteByInvoiceId(int $invoiceId): bool
    {
        $sql = "DELETE FROM line_items WHERE invoice_id = :invoice_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['invoice_id' => $invoiceId]);

        return $stmt->rowCount() > 0;
    }
}
