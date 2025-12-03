<?php

namespace App\Services;

class SolrService
{
    private string $endpoint;

    public function __construct()
    {
        $this->endpoint = $_ENV['SOLR_URL'] ?? getenv('SOLR_URL') ?: 'http://Solr.railway.internal:8983/solr/invoices';
        error_log("SolrService initialized with endpoint: " . $this->endpoint);
    }

    public function indexInvoice(array $invoice, string $lineItemsText): void
    {
        error_log("Indexing invoice ID: " . ($invoice['id'] ?? 'unknown'));
        
        $payload = [
            'add' => [
                'doc' => [
                    'id' => (string) $invoice['id'],
                    'invoice_number' => $invoice['invoice_number'] ?? '',
                    'supplier_name' => $invoice['supplier_name'] ?? '',
                    'status' => $invoice['status'] ?? '',
                    'invoice_date' => $invoice['invoice_date'] ?? '',
                    'total_amount' => (float) ($invoice['total_amount'] ?? 0),
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
        
        $result = @file_get_contents($url);
        if ($result === false) {
            error_log("Solr search failed for URL: " . $url);
            return ['response' => ['docs' => []]];
        }
        
        return json_decode($result, true) ?? ['response' => ['docs' => []]];
    }

    private function sendToSolr(array $payload): void
    {
        $url = $this->endpoint . "/update?commit=true";
        error_log("Sending to Solr: " . $url);
        error_log("Payload: " . json_encode($payload));
        
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            error_log("Solr cURL error: " . $error);
        } else {
            error_log("Solr response (HTTP $httpCode): " . $response);
        }
    }
}
