<?php

declare(strict_types=1);

namespace App\Repository;

use Predis\Client;

final class RedisTokenBlacklistRepository implements TokenBlacklistRepositoryInterface
{
    // Тот же префикс, что и в auth-service — оба сервиса читают/пишут один и тот же Redis
    private const KEY_PREFIX = 'blacklist:';

    public function __construct(private readonly Client $redis)
    {
    }

    public function isRevoked(string $jti): bool
    {
        return $this->redis->exists(self::KEY_PREFIX . $jti) > 0;
    }
}
