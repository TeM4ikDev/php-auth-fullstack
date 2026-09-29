<?php

declare(strict_types=1);

namespace App\Repository;

/** Только чтение — notification-service не выдаёт и не отзывает токены, это забота auth-service. */
interface TokenBlacklistRepositoryInterface
{
    public function isRevoked(string $jti): bool;
}
