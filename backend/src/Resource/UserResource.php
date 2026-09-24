<?php

declare(strict_types=1);

namespace App\Resource;

use App\Dto\UserDto;
use App\Dto\UserListDto;

final class UserResource
{
    public static function fromDto(UserDto $user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role->value,
            'banned' => $user->banned,
            'emailVerifiedAt' => $user->emailVerifiedAt,
            'deletedAt' => $user->deletedAt,
            'createdAt' => $user->createdAt,
            'updatedAt' => $user->updatedAt,
        ];
    }

    /** Форма {data, total} — её ждёт dataProvider в админке на Refine. */
    public static function collection(UserListDto $list): array
    {
        return [
            'data' => array_map(static fn (UserDto $user): array => self::fromDto($user), $list->items),
            'total' => $list->total,
        ];
    }
}
