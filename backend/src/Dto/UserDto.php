<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\UserRole;

final readonly class UserDto
{
    public function __construct(
        public int      $id,
        public string   $name,
        public ?string  $phone,
        public string   $email,
        public string   $passwordHash,
        public UserRole $role,
        public bool     $banned,
        public ?string  $emailVerifiedAt,
        public ?string  $deletedAt,
        public string   $createdAt,
        public ?string  $updatedAt,
    ) {
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }
}
