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
        // Utilise edismax pour chercher dans tous les champs
        // qf = query fields avec boost
        $params = http_build_query([
            'q' => $query,
            'defType' => 'edismax',
            'qf' => 'supplier_name^3 invoice_number^2 line_items_text^2 status',
            'q.op' => 'OR',
            'wt' => 'json',
            'rows' => 100
        ]);
        
        $url = $this->endpoint . "/select?" . $params;
        error_log("Solr search URL: " . $url);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5
        ]);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error || $httpCode >= 400) {
            error_log("Solr search error: " . ($error ?: "HTTP $httpCode - $result"));
            return ['response' => ['docs' => []]];
        }
        
        $decoded = json_decode($result, true);
        error_log("Solr found " . ($decoded['response']['numFound'] ?? 0) . " documents");
        
        return $decoded ?? ['response' => ['docs' => []]];
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
        } elseif ($httpCode >= 400) {
            error_log("Solr error (HTTP $httpCode): " . $response);
            // On ne propage pas l'erreur pour ne pas bloquer l'application
        } else {
            error_log("Solr indexed successfully (HTTP $httpCode)");
        }
    }
    
    public function isAvailable(): bool
    {
        $ch = curl_init($this->endpoint . "/admin/ping");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 2
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
}
