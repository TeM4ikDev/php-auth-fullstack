<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\CreateUserDto;
use App\Dto\UpdateProfileDto;
use App\Dto\UserDto;
use App\Enum\UserRole;

interface UserRepositoryInterface
{
    public function findById(int $id): ?UserDto;

    /** В отличие от findById(), возвращает и мягко удалённых — нужно админке. */
    public function findByIdIncludingDeleted(int $id): ?UserDto;

    public function findByEmail(string $email): ?UserDto;

    public function findByVerificationToken(string $token): ?UserDto;

    public function create(CreateUserDto $dto): UserDto;

    public function markEmailVerified(int $id): UserDto;

    public function update(int $id, UpdateProfileDto $dto): UserDto;

    public function updatePassword(int $id, string $passwordHash): void;

    public function softDelete(int $id): void;

    public function setBanned(int $id, bool $banned): UserDto;

    public function setRole(int $id, UserRole $role): UserDto;

    /** @return list<UserDto> */
    public function findAll(int $limit, int $offset, ?string $search = null): array;

    public function countAll(?string $search = null): int;
}
