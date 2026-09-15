<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;

final class RateLimiterService
{
    private readonly string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?? sys_get_temp_dir() . '/rate-limit';

        if (!is_dir($this->directory) && !mkdir($this->directory, 0777, true) && !is_dir($this->directory)) {
            throw new RuntimeException("Could not create rate limit directory [{$this->directory}].");
        }
    }

    public function hit(string $key, int $limit, int $windowSeconds): RateLimitResult
    {
        $path = $this->directory . '/' . hash('sha256', $key) . '.json';

        if (!is_writable($this->directory)) {
            error_log("Rate limiter: directory [{$this->directory}] is not writable, failing open.");

            return new RateLimitResult(true, $limit, 0);
        }

        $handle = fopen($path, 'c+');

        if ($handle === false) {
            error_log("Rate limiter: could not open lock file [{$path}], failing open.");

            return new RateLimitResult(true, $limit, 0);
        }

        flock($handle, LOCK_EX);

        $raw = stream_get_contents($handle);
        $state = $raw !== false && $raw !== '' ? json_decode($raw, true) : null;
        $now = time();

        if (!is_array($state) || !isset($state['windowStart'], $state['count']) || ($now - $state['windowStart']) >= $windowSeconds) {
            $state = ['windowStart' => $now, 'count' => 0];
        }

        $state['count']++;

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, (string) json_encode($state));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        $allowed = $state['count'] <= $limit;
        $retryAfter = max(0, $windowSeconds - ($now - $state['windowStart']));

        return new RateLimitResult($allowed, max(0, $limit - $state['count']), $retryAfter);
    }
}
