<?php

declare(strict_types=1);

namespace App\Repository;

use Predis\Client;

final class RedisTokenBlacklistRepository implements TokenBlacklistRepositoryInterface
{
    private const KEY_PREFIX = 'blacklist:';

    public function __construct(private readonly Client $redis)
    {
    }

    public function revoke(string $jti, int $ttlSeconds): void
    {
        // Токен уже истёк сам по себе — блокировать нечего
        if ($ttlSeconds <= 0) {
            return;
        }

        $this->redis->setex(self::KEY_PREFIX . $jti, $ttlSeconds, '1');
    }

    public function isRevoked(string $jti): bool
    {
        return $this->redis->exists(self::KEY_PREFIX . $jti) > 0;
    }
}
