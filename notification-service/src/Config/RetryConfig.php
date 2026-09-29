<?php

declare(strict_types=1);

namespace App\Config;

final class RetryConfig
{
    private const DEFAULT_DELAYS_MS = [5_000, 25_000, 125_000];

    public function __construct(
        public readonly int $maxAttempts,
        public readonly array $delaysMs,
    ) {
    }

    public static function fromConfig(Config $config): self
    {
        $raw = $config->get('RETRY_DELAYS_MS');

        $delays = $raw === null
            ? self::DEFAULT_DELAYS_MS
            : array_values(array_map('intval', array_filter(explode(',', $raw), static fn (string $v): bool => trim($v) !== '')));

        return new self($config->getInt('RETRY_MAX_ATTEMPTS', count($delays)), $delays);
    }
}
