<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\SessionDto;
use Predis\Client;

final class RedisSessionRepository implements SessionRepositoryInterface
{
    private const SESSION_PREFIX = 'session:';

    private const INDEX_PREFIX = 'sessions:';

    public function __construct(private readonly Client $redis)
    {
    }

    public function start(SessionDto $session, int $ttlSeconds): void
    {
        $this->redis->setex(
            $this->sessionKey($session->userId, $session->sessionId),
            max($ttlSeconds, 1),
            json_encode([
                'sessionId' => $session->sessionId,
                'userId' => $session->userId,
                'createdAt' => $session->createdAt,
            ], JSON_THROW_ON_ERROR),
        );

        $this->redis->sadd($this->indexKey($session->userId), [$session->sessionId]);
    }

    public function touch(int $userId, string $sessionId, int $ttlSeconds): void
    {
        $this->redis->expire($this->sessionKey($userId, $sessionId), max($ttlSeconds, 1));
    }

    public function end(int $userId, string $sessionId): void
    {
        $this->redis->del([$this->sessionKey($userId, $sessionId)]);
        $this->redis->srem($this->indexKey($userId), [$sessionId]);
    }

    public function endAll(int $userId): void
    {
        foreach ($this->listFor($userId) as $session) {
            $this->end($userId, $session->sessionId);
        }

        $this->redis->del([$this->indexKey($userId)]);
    }

    public function listFor(int $userId): array
    {
        $sessionIds = $this->redis->smembers($this->indexKey($userId));
        $sessions = [];

        foreach ($sessionIds as $sessionId) {
            $raw = $this->redis->get($this->sessionKey($userId, $sessionId));

            if ($raw === null) {
                // Сессия истекла по TTL, но осталась в индексе — подчищаем на лету
                $this->redis->srem($this->indexKey($userId), [$sessionId]);

                continue;
            }

            $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
            $sessions[] = new SessionDto($data['sessionId'], $data['userId'], $data['createdAt']);
        }

        return $sessions;
    }

    private function sessionKey(int $userId, string $sessionId): string
    {
        return self::SESSION_PREFIX . $userId . ':' . $sessionId;
    }

    private function indexKey(int $userId): string
    {
        return self::INDEX_PREFIX . $userId;
    }
}
