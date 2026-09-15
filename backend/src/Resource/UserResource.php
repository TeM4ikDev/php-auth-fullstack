<?php

declare(strict_types=1);

namespace App\Resource;

use App\Dto\UserDto;

final class UserResource
{
    public static function fromDto(UserDto $user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'createdAt' => $user->createdAt,
        ];
    }
}
