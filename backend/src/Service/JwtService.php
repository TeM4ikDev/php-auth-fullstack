<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\JwtConfig;
use App\Dto\TokenPayloadDto;
use App\Dto\UserDto;
use App\Enum\TokenType;
use App\Service\Exception\InvalidTokenException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

final class JwtService
{
    public function __construct(private readonly JwtConfig $config)
    {
    }

    public function issueAccess(UserDto $user, string $sessionId): string
    {
        return $this->issue($user, $sessionId, TokenType::Access, $this->config->accessTtlSeconds);
    }

    public function issueRefresh(UserDto $user, string $sessionId): string
    {
        return $this->issue($user, $sessionId, TokenType::Refresh, $this->config->refreshTtlSeconds);
    }

    public function accessTtlSeconds(): int
    {
        return $this->config->accessTtlSeconds;
    }

    public function refreshTtlSeconds(): int
    {
        return $this->config->refreshTtlSeconds;
    }

    public function decode(string $token): TokenPayloadDto
    {
        try {
            $decoded = JWT::decode($token, new Key($this->config->secret, $this->config->algorithm));

            return TokenPayloadDto::fromClaims((array) $decoded);
        } catch (Throwable $e) {
            throw new InvalidTokenException();
        }
    }

    private function issue(UserDto $user, string $sessionId, TokenType $type, int $ttlSeconds): string
    {
        $issuedAt = time();

        $claims = [
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role->value,
            'sid' => $sessionId,
            'jti' => bin2hex(random_bytes(16)),
            'typ' => $type->value,
            'iat' => $issuedAt,
            'exp' => $issuedAt + $ttlSeconds,
        ];

        return JWT::encode($claims, $this->config->secret, $this->config->algorithm);
    }
}
