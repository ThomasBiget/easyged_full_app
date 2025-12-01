<?php

namespace App\Services;

class SolrService
{
    private string $endpoint;

    public function __construct()
    {
        $this->endpoint = $_ENV['SOLR_URL'] ?? 'http://localhost:8983/solr/invoices';
    }

    public function indexInvoice(array $invoice, string $lineItemsText): void
    {
        $payload = [
            'add' => [
                'doc' => [
                    'id' => $invoice['id'],
                    'invoice_number' => $invoice['invoice_number'],
                    'supplier_name' => $invoice['supplier_name'],
                    'status' => $invoice['status'],
                    'invoice_date' => $invoice['invoice_date'],
                    'total_amount' => $invoice['total_amount'],
                    'line_items_text' => $lineItemsText
                ]
            ]
        ];

        $this->sendToSolr($payload);
    }

    public function deleteInvoice(int $id): void
    {
        $payload = ['delete' => ['id' => (string)$id]];
        $this->sendToSolr($payload);
    }

    public function search(string $query): array
    {
        $url = $this->endpoint . "/select?q=" . urlencode($query) . "&wt=json";
        return json_decode(file_get_contents($url), true);
    }

    private function sendToSolr(array $payload): void
    {
        $ch = curl_init($this->endpoint . "/update?commit=true");

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_exec($ch);
        curl_close($ch);
    }
}
