<?php

namespace App\Services;

use App\Repository\InvoiceRepositoryInterface;
use App\Repository\LineItemRepositoryInterface;
use App\Models\LineItem;
use App\Services\SolrService;
use Exception;
use PDO;

class InvoiceService
{
    private PDO $db;
    private InvoiceRepositoryInterface $invoiceRepository;
    private LineItemRepositoryInterface $lineItemRepository;
    private SolrService $solrService;

    public function __construct(
        PDO $db,
        InvoiceRepositoryInterface $invoiceRepository,
        LineItemRepositoryInterface $lineItemRepository,
        SolrService $solrService
    ) {
        $this->db = $db;
        $this->invoiceRepository = $invoiceRepository;
        $this->lineItemRepository = $lineItemRepository;
        $this->solrService = $solrService;
    }

    public function getAll(): array
    {
        $invoices = $this->invoiceRepository->findAll();

        foreach ($invoices as &$invoice) {
            $invoice['status'] = strtoupper($invoice['status']);
        }

        return $invoices;
    }

    public function getById(int $id): ?array
    {
        $invoice = $this->invoiceRepository->findById($id);
        if (!$invoice) {
            return null;
        }

        $invoice['is_pending'] = $invoice['status'] === 'pending';

        return $invoice;
    }

    public function getInvoiceWithLines(int $invoiceId): array
    {
        $invoice = $this->invoiceRepository->findById($invoiceId);

        if (!$invoice) {
            throw new Exception('Invoice not found');
        }

        $lineItems = $this->lineItemRepository->findByInvoiceId($invoiceId);

        return [
            'invoice' => $invoice,
            'line_items' => $lineItems
        ];
    }

    public function createInvoiceWithLines(array $data): array
    {
        $requiredFields = ['user_id', 'supplier_name', 'invoice_date', 'total_amount', 'tva_percentage', 'line_items'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        try {
            $this->db->beginTransaction();

            $tvaAmount = $data['tva_amount']
                ?? ($data['total_amount'] * $data['tva_percentage']) / 100;

            $invoiceId = $this->invoiceRepository->save([
                'user_id' => $data['user_id'],
                'supplier_name' => $data['supplier_name'],
                'invoice_number' => $data['invoice_number'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'total_amount' => $data['total_amount'],
                'tva_amount' => $tvaAmount,
                'tva_percentage' => $data['tva_percentage'],
                'status' => 'pending',
                'image_path' => $data['image_path'] ?? null
            ]);

            foreach ($data['line_items'] as $item) {
                $lineItem = new LineItem();
                $lineItem->invoice_id = $invoiceId;
                $lineItem->description = $item['description'];
                $lineItem->quantity = $item['quantity'];
                $lineItem->unit_price = $item['unit_price'];
                $lineItem->total_price = $item['quantity'] * $item['unit_price'];
                $lineItem->verified = false;

                $this->lineItemRepository->create($lineItem);
            }
            
            $text = implode(' ', array_map(
                fn($item) => $item['description'],
                $data['line_items']
            ));
            
            $this->solrService->indexInvoice([
                'id' => $invoiceId,
                'invoice_number' => $data['invoice_number'],
                'supplier_name' => $data['supplier_name'],
                'status' => 'pending',
                'invoice_date' => $data['invoice_date'],
                'total_amount' => $data['total_amount']
            ], $text);
            

            $this->db->commit();

            return [
                'invoice_id' => $invoiceId,
                'message' => 'Facture + lignes créées avec succès'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception(
                'Erreur lors de la création complète de la facture : ' . $e->getMessage()
            );
        }
    }

    public function update(int $id, array $data): ?array
    {
        $invoice = $this->invoiceRepository->findById($id);
        if (!$invoice) {
            return null;
        }

        $this->invoiceRepository->save(array_merge($invoice, $data));

        return $this->getById($id);
    }

    public function delete(int $id): bool
    {
        $invoice = $this->invoiceRepository->findById($id);
        if (!$invoice) {
            return false;
        }
    
        try {
            $this->db->beginTransaction();
    
            $this->lineItemRepository->deleteByInvoiceId($id);
    
            $this->invoiceRepository->delete($id);
    
            $this->db->commit();
    
            return true;
    
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw new \Exception(
                'Erreur lors de la suppression complète de la facture : ' . $e->getMessage()
            );
        }
    }
    
}
