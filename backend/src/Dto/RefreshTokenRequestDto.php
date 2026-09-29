<?php

declare(strict_types=1);

namespace App\Dto;

use InvalidArgumentException;

final class RefreshTokenRequestDto
{
    private function __construct(
        public readonly string $refreshToken,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $refreshToken = is_string($data['refreshToken'] ?? null) ? trim($data['refreshToken']) : '';

        if ($refreshToken === '') {
            throw new InvalidArgumentException('Refresh token is required.');
        }

        return new self($refreshToken);
    }
}
