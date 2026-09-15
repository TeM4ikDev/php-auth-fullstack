<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\CreateUserDto;
use App\Dto\UserDto;

interface UserRepositoryInterface
{
    public function findById(int $id): ?UserDto;

    public function findByEmail(string $email): ?UserDto;

    public function create(CreateUserDto $dto): UserDto;
}
