<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\RefreshTokenDto;

interface RefreshTokenRepositoryInterface
{
    public function store(string $refreshToken, int $userId, string $sessionId, int $ttlSeconds): void;

    public function findByToken(string $refreshToken): ?RefreshTokenDto;

    public function delete(string $refreshToken): void;

    public function deleteForSession(int $userId, string $sessionId): void;
}
