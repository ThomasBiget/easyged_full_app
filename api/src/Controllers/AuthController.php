<?php

namespace App\Controllers;

use App\Services\AuthService;
use Exception;

class AuthController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['email'], $data['password'], $data['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Email, mot de passe et nom requis']);
                return;
            }

            $userId = $this->authService->register($data['email'], $data['password'], $data['name']);

            http_response_code(201);
            echo json_encode(['user_id' => $userId]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function login(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['email'], $data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Email et mot de passe requis']);
                return;
            }

            $result = $this->authService->login(
                $data['email'],
                $data['password']
            );

            http_response_code(200);
            echo json_encode($result);

        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode([
                'error' => $e->getMessage()
            ]);
        }
    }
}
