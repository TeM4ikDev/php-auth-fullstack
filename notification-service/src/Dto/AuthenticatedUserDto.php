<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\UserRole;

final class AuthenticatedUserDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly UserRole $role,
    ) {
    }
}
