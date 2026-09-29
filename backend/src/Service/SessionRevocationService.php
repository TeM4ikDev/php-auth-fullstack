<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\RefreshTokenRepositoryInterface;
use App\Repository\SessionRepositoryInterface;

/** Массовый отзыв всех сессий пользователя — бан, смена роли, смена пароля, удаление аккаунта. */
final class SessionRevocationService
{
    public function __construct(
        private readonly SessionRepositoryInterface $sessions,
        private readonly RefreshTokenRepositoryInterface $refreshTokens,
    ) {
    }

    public function revokeAllSessions(int $userId): void
    {
        foreach ($this->sessions->listFor($userId) as $session) {
            $this->refreshTokens->deleteForSession($userId, $session->sessionId);
        }

        $this->sessions->endAll($userId);
    }
}
