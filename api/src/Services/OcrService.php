<?php

namespace App\Services;

use Exception;

class OcrService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    private string $model = 'claude-sonnet-4-5';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Extrait les informations d'une facture à partir d'une image ou d'un PDF
     */
    public function extractInvoiceData(string $filePath): array
    {
        $mimeType = $this->getMimeType($filePath);
        $base64Content = base64_encode(file_get_contents($filePath));

        $prompt = $this->buildPrompt();

        $response = $this->callClaudeApi($base64Content, $mimeType, $prompt);

        return $this->parseResponse($response);
    }

    /**
     * Construit le prompt pour l'extraction de facture
     */
    private function buildPrompt(): string
    {
        return <<<PROMPT
Tu es un assistant spécialisé dans l'extraction de données de factures.

Analyse cette image/document et extrais les informations suivantes au format JSON strict :

{
    "supplier_name": "Nom du fournisseur",
    "invoice_number": "Numéro de facture",
    "invoice_date": "YYYY-MM-DD",
    "total_amount": 0.00,
    "tva_amount": 0.00,
    "tva_percentage": 0.00,
    "line_items": [
        {
            "description": "Description du produit/service",
            "quantity": 1,
            "unit_price": 0.00
        }
    ]
}

Règles importantes :
- Retourne UNIQUEMENT le JSON, sans texte avant ou après
- Les montants doivent être des nombres décimaux (pas de symboles €)
- La date doit être au format YYYY-MM-DD
- Si une information n'est pas trouvée, utilise null
- tva_percentage est le taux de TVA (ex: 20 pour 20%)
- Extrais toutes les lignes de la facture dans line_items

PROMPT;
    }

    /**
     * Appelle l'API Claude avec l'image
     */
    private function callClaudeApi(string $base64Content, string $mimeType, string $prompt): array
    {
        $payload = [
            'model' => $this->model,
            'max_tokens' => 4096,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mimeType,
                                'data' => $base64Content
                            ]
                        ],
                        [
                            'type' => 'text',
                            'text' => $prompt
                        ]
                    ]
                ]
            ]
        ];

        $ch = curl_init($this->apiUrl);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new Exception("Erreur cURL : $error");
        }

        if ($httpCode !== 200) {
            throw new Exception("Erreur API Claude (HTTP $httpCode) : $response");
        }

        return json_decode($response, true);
    }

    private function parseResponse(array $response): array
    {
        if (!isset($response['content'][0]['text'])) {
            throw new Exception('Réponse Claude invalide');
        }

        $text = $response['content'][0]['text'];

        $text = trim($text);
        
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $text, $matches)) {
            $text = $matches[1];
        }

        $data = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Impossible de parser le JSON retourné par Claude : ' . $text);
        }

        return $data;
    }

    private function getMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf'
        ];

        if (!isset($mimeTypes[$extension])) {
            throw new Exception("Type de fichier non supporté : $extension");
        }

        return $mimeTypes[$extension];
    }
}

