<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\UserRole;
use InvalidArgumentException;

final class AdminUpdateUserDto
{
    private function __construct(
        public readonly string $name,
        public readonly ?string $phone,
        public readonly string $email,
        public readonly UserRole $role,
        public readonly bool $banned,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $profile = UpdateProfileDto::fromArray($data);
        $rawRole = is_string($data['role'] ?? null) ? strtoupper(trim($data['role'])) : '';
        $role = UserRole::tryFrom($rawRole);

        if ($role === null) {
            throw new InvalidArgumentException('Invalid role.');
        }

        if (!is_bool($data['banned'] ?? null)) {
            throw new InvalidArgumentException('Field "banned" must be a boolean.');
        }

        return new self($profile->name, $profile->phone, $profile->email, $role, $data['banned']);
    }
}
