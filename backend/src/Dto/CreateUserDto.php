<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\UserRole;

final class CreateUserDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly UserRole $role = UserRole::User,
    ) {
    }
}
