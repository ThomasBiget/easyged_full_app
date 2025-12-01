<?php

namespace App\Repository;

use PDO;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM invoices WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ?: null;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query(
            "SELECT * FROM invoices ORDER BY created_at DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save(array $data): int
    {
        if (isset($data['id'])) {
            $sql = "
                UPDATE invoices SET 
                    user_id = :user_id,
                    supplier_name = :supplier_name,
                    invoice_number = :invoice_number,
                    invoice_date = :invoice_date,
                    total_amount = :total_amount,
                    tva_amount = :tva_amount,
                    tva_percentage = :tva_percentage,
                    status = :status,
                    image_path = :image_path,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                'id' => $data['id'],
                'user_id' => $data['user_id'],
                'supplier_name' => $data['supplier_name'],
                'invoice_number' => $data['invoice_number'],
                'invoice_date' => $data['invoice_date'],
                'total_amount' => $data['total_amount'],
                'tva_amount' => $data['tva_amount'],
                'tva_percentage' => $data['tva_percentage'],
                'status' => $data['status'],
                'image_path' => $data['image_path']
            ]);

            return (int)$data['id'];

        } else {
            $sql = "
                INSERT INTO invoices 
                (user_id, supplier_name, invoice_number, invoice_date, total_amount, tva_amount, tva_percentage, status, image_path) 
                VALUES 
                (:user_id, :supplier_name, :invoice_number, :invoice_date, :total_amount, :tva_amount, :tva_percentage, :status, :image_path)
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                'user_id' => $data['user_id'],
                'supplier_name' => $data['supplier_name'],
                'invoice_number' => $data['invoice_number'],
                'invoice_date' => $data['invoice_date'],
                'total_amount' => $data['total_amount'],
                'tva_amount' => $data['tva_amount'],
                'tva_percentage' => $data['tva_percentage'],
                'status' => $data['status'],
                'image_path' => $data['image_path']
            ]);

            return (int)$this->db->lastInsertId();
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM invoices WHERE id = :id"
        );

        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
