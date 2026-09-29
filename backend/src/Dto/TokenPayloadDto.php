<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\TokenType;
use App\Enum\UserRole;
use UnexpectedValueException;

final class TokenPayloadDto
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
        public readonly UserRole $role,
        public readonly string $jti,
        public readonly string $sessionId,
        public readonly TokenType $type,
        public readonly int $expiresAt,
    ) {
    }

    public static function fromClaims(array $claims): self
    {
        if (!isset($claims['sub'], $claims['email'], $claims['role'], $claims['jti'], $claims['sid'], $claims['typ'], $claims['exp'])) {
            throw new UnexpectedValueException('Token is missing required claims.');
        }

        $role = UserRole::tryFrom((string) $claims['role']);
        $type = TokenType::tryFrom((string) $claims['typ']);

        if ($role === null || $type === null) {
            throw new UnexpectedValueException('Token carries an unknown role or type claim.');
        }

        return new self(
            (int) $claims['sub'],
            (string) $claims['email'],
            $role,
            (string) $claims['jti'],
            (string) $claims['sid'],
            $type,
            (int) $claims['exp'],
        );
    }
}
