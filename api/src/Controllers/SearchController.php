<?php

namespace App\Controllers;

use App\Services\SolrService;

class SearchController
{
    private SolrService $solrService;

    public function __construct(SolrService $solrService)
    {
        $this->solrService = $solrService;
    }

    public function search(): void
    {
        $q = $_GET['q'] ?? '';

        if (!$q) {
            http_response_code(400);
            echo json_encode(['error' => 'Query manquante']);
            return;
        }

        $results = $this->solrService->search($q);
        echo json_encode($results['response']['docs']);
    }
}
