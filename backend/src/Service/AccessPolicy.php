<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\UserDto;

/**
 * ABAC поверх RBAC: роль даёт базовые права, здесь проверяются атрибуты —
 * владение ресурсом и защита администратора от действий против самого себя.
 */
final class AccessPolicy
{
    public function canViewUser(UserDto $actor, int $targetUserId): bool
    {
        return $this->isSelf($actor, $targetUserId) || $actor->role->canManageUsers();
    }

    public function canUpdateUser(UserDto $actor, int $targetUserId): bool
    {
        return $this->isSelf($actor, $targetUserId) || $actor->role->canManageUsers();
    }

    /** Самобан отрезал бы администратору доступ к системе без возможности восстановиться. */
    public function canBan(UserDto $actor, int $targetUserId): bool
    {
        return $actor->role->canManageUsers() && !$this->isSelf($actor, $targetUserId);
    }

    /** По той же причине админ не может понизить собственную роль. */
    public function canChangeRole(UserDto $actor, int $targetUserId): bool
    {
        return $actor->role->canManageUsers() && !$this->isSelf($actor, $targetUserId);
    }

    private function isSelf(UserDto $actor, int $targetUserId): bool
    {
        return $actor->id === $targetUserId;
    }
}
