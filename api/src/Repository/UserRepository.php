<?php

namespace App\Repository;

use PDO;

class UserRepository implements UserRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE email = :email LIMIT 1"
        );

        $stmt->execute([
            'email' => $email
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE id = :id LIMIT 1"
        );

        $stmt->execute([
            'id' => $id
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }


    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (email, password_hash, name)
            VALUES (:email, :password_hash, :name)
        ");

        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }
}
