<?php

declare(strict_types=1);

namespace App\Service;

final class RateLimiterService
{
    private $dir;

    public function __construct(?string $dir = null)
    {
        $this->dir = $dir ?? sys_get_temp_dir() . '/rate-limit';

        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0777, true);
        }
    }


    public function hit(string $key, int $limit, int $windowSeconds): array
    {
        $path = $this->dir . '/' . hash('sha256', $key) . '.json';
        $handle = @fopen($path, 'c+');

        if ($handle === false) {
            return ['allowed' => true, 'remaining' => $limit, 'retryAfter' => 0];
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
        fwrite($handle, json_encode($state));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        $allowed = $state['count'] <= $limit;
        $retryAfter = max(0, $windowSeconds - ($now - $state['windowStart']));

        return [
            'allowed' => $allowed,
            'remaining' => max(0, $limit - $state['count']),
            'retryAfter' => $retryAfter,
        ];
    }
}
