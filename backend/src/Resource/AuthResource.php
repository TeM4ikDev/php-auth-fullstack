<?php

declare(strict_types=1);

namespace App\Resource;

use App\Dto\AuthResultDto;

final class AuthResource
{
    public static function fromDto(AuthResultDto $result): array
    {
        return [
            'accessToken' => $result->accessToken,
            'refreshToken' => $result->refreshToken,
            'expiresIn' => $result->expiresIn,
            'user' => UserResource::fromDto($result->user),
        ];
    }
}
