<?php

declare(strict_types=1);

namespace App\Dto;

use InvalidArgumentException;

final class VerifyEmailDto
{
    private function __construct(
        public readonly string $token,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $token = is_string($data['token'] ?? null) ? trim($data['token']) : '';

        if ($token === '') {
            throw new InvalidArgumentException('Verification token is required.');
        }

        return new self($token);
    }
}
