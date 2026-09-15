<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\UserRole;

final readonly class UserDto
{
    public function __construct(
        public int      $id,
        public string   $name,
        public string   $email,
        public string   $passwordHash,
        public UserRole $role,
        public string   $createdAt,
    ) {
    }
}
