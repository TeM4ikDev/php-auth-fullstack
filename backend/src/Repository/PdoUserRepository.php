<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\CreateUserDto;
use App\Dto\UserDto;
use App\Enum\UserRole;
use App\Repository\Exception\EmailAlreadyTakenException;
use PDO;
use PDOException;
use RuntimeException;

final class PdoUserRepository implements UserRepositoryInterface
{
    private const INSERT = 'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)';

    private const FIND_BY_EMAIL = 'SELECT id, name, email, password_hash, role, created_at FROM users WHERE email = :email LIMIT 1';

    private const FIND_BY_ID = 'SELECT id, name, email, password_hash, role, created_at FROM users WHERE id = :id LIMIT 1';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?UserDto
    {
        $statement = $this->pdo->prepare(self::FIND_BY_ID);
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function findByEmail(string $email): ?UserDto
    {
        $statement = $this->pdo->prepare(self::FIND_BY_EMAIL);
        $statement->execute(['email' => $email]);
        $row = $statement->fetch();

        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function create(CreateUserDto $dto): UserDto
    {
        $statement = $this->pdo->prepare(self::INSERT);

        try {
            $statement->execute([
                'name' => $dto->name,
                'email' => $dto->email,
                'password_hash' => $dto->passwordHash,
                'role' => $dto->role->value,
            ]);
        } catch (PDOException $e) {
            if (in_array($e->getCode(), ['23505', '23000'], true)) {
                throw new EmailAlreadyTakenException($dto->email);
            }

            throw $e;
        }

        $user = $this->findByEmail($dto->email);

        if ($user === null) {
            throw new RuntimeException('User was inserted but could not be re-read.');
        }

        return $user;
    }

    private function hydrate(array $row): UserDto
    {
        return new UserDto(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['email'],
            (string) $row['password_hash'],
            UserRole::from((string) $row['role']),
            (string) $row['created_at'],
        );
    }
}
