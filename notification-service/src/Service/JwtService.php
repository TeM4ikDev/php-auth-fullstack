<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\JwtConfig;
use App\Dto\TokenPayloadDto;
use App\Service\Exception\InvalidTokenException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

/** Сервис только проверяет токены auth-service — сам он их не выпускает. */
final class JwtService
{
    public function __construct(private readonly JwtConfig $config)
    {
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
}
