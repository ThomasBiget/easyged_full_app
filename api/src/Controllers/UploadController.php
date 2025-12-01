<?php

namespace App\Controllers;

use App\Services\DocumentService;
use App\Services\OcrService;
use App\Services\InvoiceService;
use Exception;

class UploadController
{
    private DocumentService $documentService;
    private OcrService $ocrService;
    private InvoiceService $invoiceService;

    public function __construct(
        DocumentService $documentService,
        OcrService $ocrService,
        InvoiceService $invoiceService
    ) {
        $this->documentService = $documentService;
        $this->ocrService = $ocrService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * POST /upload
     * Upload un document et extrait les données via OCR
     */
    public function upload(): void
    {
        try {
            // Vérifie qu'un fichier a été envoyé
            if (!isset($_FILES['document'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Aucun fichier envoyé. Utilisez le champ "document"']);
                return;
            }

            // Récupère l'ID utilisateur depuis le token JWT
            $userId = $_SERVER['user']['sub'] ?? null;
            if (!$userId) {
                http_response_code(401);
                echo json_encode(['error' => 'Utilisateur non authentifié']);
                return;
            }

            // 1. Upload du fichier
            $filePath = $this->documentService->upload($_FILES['document']);

            // 2. Extraction OCR via Claude
            $extractedData = $this->ocrService->extractInvoiceData($filePath);

            // 3. Prépare les données pour créer la facture
            $invoiceData = [
                'user_id' => (int) $userId,
                'supplier_name' => $extractedData['supplier_name'] ?? 'Non identifié',
                'invoice_number' => $extractedData['invoice_number'] ?? null,
                'invoice_date' => $extractedData['invoice_date'] ?? date('Y-m-d'),
                'total_amount' => (float) ($extractedData['total_amount'] ?? 0),
                'tva_amount' => (float) ($extractedData['tva_amount'] ?? 0),
                'tva_percentage' => (float) ($extractedData['tva_percentage'] ?? 0),
                'image_path' => $filePath,
                'line_items' => $extractedData['line_items'] ?? []
            ];

            // 4. Crée la facture avec les lignes
            $result = $this->invoiceService->createInvoiceWithLines($invoiceData);

            // 5. Retourne le résultat
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Document analysé et facture créée avec succès',
                'invoice_id' => $result['invoice_id'],
                'extracted_data' => $extractedData,
                'file_path' => $filePath
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * POST /upload/analyze
     * Analyse un document sans créer de facture (preview)
     */
    public function analyze(): void
    {
        try {
            if (!isset($_FILES['document'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Aucun fichier envoyé. Utilisez le champ "document"']);
                return;
            }

            // 1. Upload du fichier
            $filePath = $this->documentService->upload($_FILES['document']);

            // 2. Extraction OCR via Claude
            $extractedData = $this->ocrService->extractInvoiceData($filePath);

            // 3. Retourne les données extraites sans créer de facture
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Document analysé avec succès',
                'extracted_data' => $extractedData,
                'file_path' => $filePath
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => $e->getMessage()
            ]);
        }
    }
}

