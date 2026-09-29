<?php

declare(strict_types=1);

namespace App\Repository;

interface TokenBlacklistRepositoryInterface
{
    public function revoke(string $jti, int $ttlSeconds): void;

    public function isRevoked(string $jti): bool;
}
