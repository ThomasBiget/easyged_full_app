<?php

namespace App\Controllers;

use App\Services\InvoiceService;
use Exception;

class InvoiceController
{
    private InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(): void
    {
        $invoices = $this->invoiceService->getAll();
        echo json_encode($invoices);
    }


    public function store(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }

        try {
            $result = $this->invoiceService->createInvoiceWithLines($data);
            echo json_encode($result);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function show(): void
    {
        $id = $_GET['id'] ?? null;
        $withLines = $_GET['with_lines'] ?? false;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id']);
            return;
        }

        $result = $withLines
            ? $this->invoiceService->getInvoiceWithLines((int)$id)
            : $this->invoiceService->getById((int)$id);

        echo json_encode($result);
    }

    public function update(): void
    {
        $id = $_GET['id'] ?? null;
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$id || !$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing ID or data']);
            return;
        }

        $invoice = $this->invoiceService->update((int)$id, $data);

        if (!$invoice) {
            http_response_code(404);
            echo json_encode(['error' => 'Invoice not found']);
            return;
        }

        echo json_encode($invoice);
    }

    public function delete(): void
    {
        $id = $_GET['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing invoice id']);
            return;
        }

        $success = $this->invoiceService->delete((int)$id);

        if (!$success) {
            http_response_code(404);
            echo json_encode(['error' => 'Invoice not found']);
            return;
        }

        echo json_encode(['message' => 'Invoice deleted successfully']);
    }
}
