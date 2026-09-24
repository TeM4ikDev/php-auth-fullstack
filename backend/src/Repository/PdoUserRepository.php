<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\CreateUserDto;
use App\Dto\UpdateProfileDto;
use App\Dto\UserDto;
use App\Enum\UserRole;
use App\Repository\Exception\EmailAlreadyTakenException;
use PDO;
use PDOException;
use RuntimeException;

final class PdoUserRepository implements UserRepositoryInterface
{
    private const COLUMNS = 'id, name, phone, email, password_hash, role, banned, email_verified_at, deleted_at, created_at, updated_at';

    private const INSERT = 'INSERT INTO users (name, phone, email, password_hash, role, email_verification_token) VALUES (:name, :phone, :email, :password_hash, :role, :email_verification_token) RETURNING ' . self::COLUMNS;

    private const FIND_BY_EMAIL = 'SELECT ' . self::COLUMNS . ' FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1';

    private const FIND_BY_ID = 'SELECT ' . self::COLUMNS . ' FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1';

    private const FIND_BY_ID_WITH_DELETED = 'SELECT ' . self::COLUMNS . ' FROM users WHERE id = :id LIMIT 1';

    private const FIND_BY_VERIFICATION_TOKEN = 'SELECT ' . self::COLUMNS . ' FROM users WHERE email_verification_token = :token AND deleted_at IS NULL LIMIT 1';

    private const MARK_EMAIL_VERIFIED = 'UPDATE users SET email_verified_at = NOW(), email_verification_token = NULL, updated_at = NOW() WHERE id = :id RETURNING ' . self::COLUMNS;

    private const UPDATE_PROFILE = 'UPDATE users SET name = :name, phone = :phone, email = :email, updated_at = NOW() WHERE id = :id AND deleted_at IS NULL RETURNING ' . self::COLUMNS;

    private const UPDATE_PASSWORD = 'UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id AND deleted_at IS NULL';

    private const SOFT_DELETE = 'UPDATE users SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id AND deleted_at IS NULL';

    private const SET_BANNED = 'UPDATE users SET banned = :banned, updated_at = NOW() WHERE id = :id RETURNING ' . self::COLUMNS;

    private const SET_ROLE = 'UPDATE users SET role = :role, updated_at = NOW() WHERE id = :id RETURNING ' . self::COLUMNS;

    private const SEARCH_CONDITION = ' AND (name ILIKE :search OR email ILIKE :search OR phone ILIKE :search)';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?UserDto
    {
        return $this->fetchOne(self::FIND_BY_ID, ['id' => $id]);
    }

    public function findByIdIncludingDeleted(int $id): ?UserDto
    {
        return $this->fetchOne(self::FIND_BY_ID_WITH_DELETED, ['id' => $id]);
    }

    public function findByEmail(string $email): ?UserDto
    {
        return $this->fetchOne(self::FIND_BY_EMAIL, ['email' => $email]);
    }

    public function findByVerificationToken(string $token): ?UserDto
    {
        return $this->fetchOne(self::FIND_BY_VERIFICATION_TOKEN, ['token' => $token]);
    }

    public function create(CreateUserDto $dto): UserDto
    {
        $statement = $this->pdo->prepare(self::INSERT);

        try {
            $statement->execute([
                'name' => $dto->name,
                'phone' => $dto->phone,
                'email' => $dto->email,
                'password_hash' => $dto->passwordHash,
                'role' => $dto->role->value,
                'email_verification_token' => $dto->emailVerificationToken,
            ]);
        } catch (PDOException $e) {
            if (in_array($e->getCode(), ['23505', '23000'], true)) {
                throw new EmailAlreadyTakenException($dto->email);
            }

            throw $e;
        }

        return $this->hydrateOrFail($statement->fetch(), 'User was inserted but could not be read back.');
    }

    public function markEmailVerified(int $id): UserDto
    {
        $statement = $this->pdo->prepare(self::MARK_EMAIL_VERIFIED);
        $statement->execute(['id' => $id]);

        return $this->hydrateOrFail($statement->fetch(), sprintf('User [%d] could not be updated.', $id));
    }

    public function update(int $id, UpdateProfileDto $dto): UserDto
    {
        $statement = $this->pdo->prepare(self::UPDATE_PROFILE);

        try {
            $statement->execute([
                'id' => $id,
                'name' => $dto->name,
                'phone' => $dto->phone,
                'email' => $dto->email,
            ]);
        } catch (PDOException $e) {
            if (in_array($e->getCode(), ['23505', '23000'], true)) {
                throw new EmailAlreadyTakenException($dto->email);
            }

            throw $e;
        }

        return $this->hydrateOrFail($statement->fetch(), sprintf('User [%d] could not be updated.', $id));
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $statement = $this->pdo->prepare(self::UPDATE_PASSWORD);
        $statement->execute(['id' => $id, 'password_hash' => $passwordHash]);
    }

    public function softDelete(int $id): void
    {
        $statement = $this->pdo->prepare(self::SOFT_DELETE);
        $statement->execute(['id' => $id]);
    }

    public function setBanned(int $id, bool $banned): UserDto
    {
        $statement = $this->pdo->prepare(self::SET_BANNED);
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->bindValue('banned', $banned, PDO::PARAM_BOOL);
        $statement->execute();

        return $this->hydrateOrFail($statement->fetch(), sprintf('User [%d] could not be updated.', $id));
    }

    public function setRole(int $id, UserRole $role): UserDto
    {
        $statement = $this->pdo->prepare(self::SET_ROLE);
        $statement->execute(['id' => $id, 'role' => $role->value]);

        return $this->hydrateOrFail($statement->fetch(), sprintf('User [%d] could not be updated.', $id));
    }

    public function findAll(int $limit, int $offset, ?string $search = null): array
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM users WHERE deleted_at IS NULL';
        $sql .= $this->searchClause($search);
        $sql .= ' ORDER BY id DESC LIMIT :limit OFFSET :offset';

        $statement = $this->pdo->prepare($sql);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $this->bindSearch($statement, $search);
        $statement->execute();

        return array_map(fn (array $row): UserDto => $this->hydrate($row), $statement->fetchAll());
    }

    public function countAll(?string $search = null): int
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE deleted_at IS NULL' . $this->searchClause($search);

        $statement = $this->pdo->prepare($sql);
        $this->bindSearch($statement, $search);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    private function searchClause(?string $search): string
    {
        return $this->normalizeSearch($search) === null ? '' : self::SEARCH_CONDITION;
    }

    private function bindSearch(\PDOStatement $statement, ?string $search): void
    {
        $normalized = $this->normalizeSearch($search);

        if ($normalized !== null) {
            $statement->bindValue('search', '%' . $normalized . '%');
        }
    }

    private function normalizeSearch(?string $search): ?string
    {
        $trimmed = $search === null ? '' : trim($search);

        return $trimmed === '' ? null : $trimmed;
    }

    private function fetchOne(string $sql, array $params): ?UserDto
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return is_array($row) ? $this->hydrate($row) : null;
    }

    private function hydrateOrFail(mixed $row, string $message): UserDto
    {
        if (!is_array($row)) {
            throw new RuntimeException($message);
        }

        return $this->hydrate($row);
    }

    private function hydrate(array $row): UserDto
    {
        return new UserDto(
            (int) $row['id'],
            (string) $row['name'],
            $row['phone'] === null ? null : (string) $row['phone'],
            (string) $row['email'],
            (string) $row['password_hash'],
            UserRole::from((string) $row['role']),
            (bool) $row['banned'],
            $row['email_verified_at'] === null ? null : (string) $row['email_verified_at'],
            $row['deleted_at'] === null ? null : (string) $row['deleted_at'],
            (string) $row['created_at'],
            $row['updated_at'] === null ? null : (string) $row['updated_at'],
        );
    }
}
