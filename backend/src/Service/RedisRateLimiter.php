<?php

declare(strict_types=1);

namespace App\Service;

use Predis\Client;
use Throwable;

final class RedisRateLimiter implements RateLimiterInterface
{
    private const KEY_PREFIX = 'ratelimit:';

    public function __construct(private readonly Client $redis)
    {
    }

    public function hit(string $key, int $limit, int $windowSeconds): RateLimitResult
    {
        $redisKey = self::KEY_PREFIX . hash('sha256', $key);

        try {
            $count = (int) $this->redis->incr($redisKey);

            if ($count === 1) {
                $this->redis->expire($redisKey, $windowSeconds);
            }

            $ttl = (int) $this->redis->ttl($redisKey);
            $retryAfter = $ttl > 0 ? $ttl : $windowSeconds;
        } catch (Throwable $e) {
            error_log('Rate limiter: Redis unavailable, failing open: ' . $e->getMessage());

            return new RateLimitResult(true, $limit, 0);
        }

        return new RateLimitResult($count <= $limit, max(0, $limit - $count), $retryAfter);
    }
}
