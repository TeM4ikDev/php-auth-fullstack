<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\Database;
use App\Database\Sql;
use App\Dto\LoginDto;
use App\Dto\RegisterDto;
use PDO;
use PDOException;
use RuntimeException;

final class AuthService
{
    private $db;
    private $password;
    private $jwt;

    public function __construct(?PDO $db = null, ?PasswordService $password = null, ?JwtService $jwt = null)
    {
        $this->db = $db ?? Database::connect();
        $this->password = $password ?? new PasswordService();
        $this->jwt = $jwt ?? new JwtService();
    }

    public function register(RegisterDto $dto): array
    {
        $statement = $this->db->prepare(Sql::INSERT_USER);

        try {
            $statement->execute([
                ':name' => $dto->name,
                ':email' => $dto->email,
                ':password_hash' => $this->password->hash($dto->password),
                ':role' => 'USER',
            ]);
        } catch (PDOException $e) {
            if (in_array($e->getCode(), ['23505', '23000'], true)) {
                throw new RuntimeException('Email already used', 409, $e);
            }

            throw $e;
        }

        $user = $this->findByEmail($dto->email);

        if ($user === null) throw new RuntimeException('Error', 500);

        return $this->authResponse($user);
    }

    public function login(LoginDto $dto): array
    {
        $user = $this->findByEmail($dto->email);

        if ($user === null || !$this->password->verify($dto->password, $user['password_hash'])) {
            throw new RuntimeException('Incorrect email or password', 401);
        }

        return $this->authResponse($user);
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare(Sql::FIND_USER_BY_ID);
        $statement->execute([':id' => $id]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare(Sql::FIND_USER_BY_EMAIL);
        $statement->execute([':email' => $email]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function publicUser(array $user): array
    {
        return [
            'id' => (string) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'createdAt' => $user['created_at'] ?? null,
        ];
    }

    private function authResponse(array $user): array
    {
        return [
            'token' => $this->jwt->encode(['sub' => (int) $user['id'], 'email' => $user['email']]),
            'user' => $this->publicUser($user),
        ];
    }
}
