<?php

declare(strict_types=1);

namespace App\Resource;

use App\Dto\AuthResultDto;

final class AuthResource
{
    public static function fromDto(AuthResultDto $result): array
    {
        return [
            'token' => $result->token,
            'user' => UserResource::fromDto($result->user),
        ];
    }
}
