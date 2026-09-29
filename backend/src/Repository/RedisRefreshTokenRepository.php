<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\RefreshTokenDto;
use Predis\Client;

final class RedisRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    private const TOKEN_PREFIX = 'refresh:';

    private const SESSION_INDEX_PREFIX = 'refresh:session:';

    public function __construct(private readonly Client $redis)
    {
    }

    public function store(string $refreshToken, int $userId, string $sessionId, int $ttlSeconds): void
    {
        $ttl = max($ttlSeconds, 1);
        $hash = $this->hash($refreshToken);

        // В Redis попадает только хэш токена — сам токен нигде не хранится в открытом виде
        $this->redis->setex($this->tokenKey($hash), $ttl, json_encode([
            'userId' => $userId,
            'sessionId' => $sessionId,
        ], JSON_THROW_ON_ERROR));

        $this->redis->setex($this->sessionIndexKey($userId, $sessionId), $ttl, $hash);
    }

    public function findByToken(string $refreshToken): ?RefreshTokenDto
    {
        $raw = $this->redis->get($this->tokenKey($this->hash($refreshToken)));

        if ($raw === null) {
            return null;
        }

        $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);

        return new RefreshTokenDto($data['userId'], $data['sessionId']);
    }

    public function delete(string $refreshToken): void
    {
        $token = $this->findByToken($refreshToken);

        $this->redis->del([$this->tokenKey($this->hash($refreshToken))]);

        if ($token !== null) {
            $this->redis->del([$this->sessionIndexKey($token->userId, $token->sessionId)]);
        }
    }

    public function deleteForSession(int $userId, string $sessionId): void
    {
        $indexKey = $this->sessionIndexKey($userId, $sessionId);
        $hash = $this->redis->get($indexKey);

        if ($hash !== null) {
            $this->redis->del([$this->tokenKey($hash)]);
        }

        $this->redis->del([$indexKey]);
    }

    private function hash(string $refreshToken): string
    {
        return hash('sha256', $refreshToken);
    }

    private function tokenKey(string $hash): string
    {
        return self::TOKEN_PREFIX . $hash;
    }

    private function sessionIndexKey(int $userId, string $sessionId): string
    {
        return self::SESSION_INDEX_PREFIX . $userId . ':' . $sessionId;
    }
}
