<?php

declare(strict_types=1);

namespace App\Service;

interface RateLimiterInterface
{
    public function hit(string $key, int $limit, int $windowSeconds): RateLimitResult;
}
