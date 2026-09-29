<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\SessionDto;

interface SessionRepositoryInterface
{
    public function start(SessionDto $session, int $ttlSeconds): void;

    public function touch(int $userId, string $sessionId, int $ttlSeconds): void;

    public function end(int $userId, string $sessionId): void;

    public function endAll(int $userId): void;

    public function listFor(int $userId): array;
}
