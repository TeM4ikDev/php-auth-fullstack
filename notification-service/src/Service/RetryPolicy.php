<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\RetryConfig;

final class RetryPolicy
{
    public function __construct(private readonly RetryConfig $config)
    {
    }

    public function isExhausted(int $attempt): bool
    {
        return $attempt > $this->config->maxAttempts;
    }

    public function delayFor(int $attempt): ?int
    {
        if ($this->isExhausted($attempt)) {
            return null;
        }

        return $this->config->delaysMs[$attempt - 1] ?? null;
    }
}
