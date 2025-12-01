<?php

namespace App\Services;

use App\Repository\UserRepositoryInterface;
use Exception;

class AuthService
{
    private UserRepositoryInterface $userRepository;
    private JwtService $jwtService;

    public function __construct(
        UserRepositoryInterface $userRepository,
        JwtService $jwtService
    ) {
        $this->userRepository = $userRepository;
        $this->jwtService = $jwtService;
    }

    public function register(string $email, string $password, string $name): int
    {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        return $this->userRepository->create([
            'email' => $email,
            'password_hash' => $hashedPassword,
            'name' => $name
        ]);
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            throw new Exception('Utilisateur introuvable');
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception('Mot de passe incorrect');
        }

        $jwt = $this->jwtService->generate([
            'sub' => $user['id'],
            'email' => $user['email']
        ]);

        return [
            'token' => $jwt,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name']
            ]
        ];
    }
}
