<?php

namespace App\Middleware;

use App\Services\JwtService;

class JwtMiddleware
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function handle(): ?array
    {
        $headers = getallheaders();

        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Token manquant']);
            exit;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $payload = $this->jwtService->verify($token);

        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Token invalide']);
            exit;
        }

        $_SERVER['user'] = $payload;

        return $payload;
    }
}
