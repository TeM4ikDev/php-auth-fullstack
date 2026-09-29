<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\AdminUpdateUserDto;
use App\Dto\UpdateProfileDto;
use App\Dto\UserDto;
use App\Dto\UserListDto;
use App\Enum\UserRole;
use App\Http\Exception\ForbiddenException;
use App\Http\Exception\NotFoundException;
use App\Repository\UserRepositoryInterface;

final class UserManagementService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AccessPolicy $policy,
        private readonly SessionRevocationService $sessionRevocation,
    ) {
    }

    public function list(int $page, int $perPage, ?string $search = null): UserListDto
    {
        $offset = ($page - 1) * $perPage;

        return new UserListDto(
            $this->users->findAll($perPage, $offset, $search),
            $this->users->countAll($search),
        );
    }

    public function find(int $id): UserDto
    {
        $user = $this->users->findByIdIncludingDeleted($id);

        if ($user === null) {
            throw new NotFoundException(sprintf('User [%d] not found.', $id));
        }

        return $user;
    }

    public function update(UserDto $actor, int $id, AdminUpdateUserDto $dto): UserDto
    {
        $target = $this->find($id);

        if (!$this->policy->canUpdateUser($actor, $target->id)) {
            throw new ForbiddenException();
        }

        if ($target->role !== $dto->role && !$this->policy->canChangeRole($actor, $target->id)) {
            throw new ForbiddenException('You cannot change your own role.');
        }

        if ($target->banned !== $dto->banned && !$this->policy->canBan($actor, $target->id)) {
            throw new ForbiddenException('You cannot ban yourself.');
        }

        $updated = $this->users->update($target->id, UpdateProfileDto::fromArray([
            'name' => $dto->name,
            'phone' => $dto->phone,
            'email' => $dto->email,
        ]));

        if ($target->role !== $dto->role) {
            $updated = $this->users->setRole($updated->id, $dto->role);
            $this->sessionRevocation->revokeAllSessions($updated->id);
        }

        if ($target->banned !== $dto->banned) {
            $updated = $this->users->setBanned($updated->id, $dto->banned);
            $this->sessionRevocation->revokeAllSessions($updated->id);
        }

        return $updated;
    }

    public function setBanned(UserDto $actor, int $id, bool $banned): UserDto
    {
        $target = $this->find($id);

        if (!$this->policy->canBan($actor, $target->id)) {
            throw new ForbiddenException('You cannot ban yourself.');
        }

        $updated = $this->users->setBanned($target->id, $banned);
        $this->sessionRevocation->revokeAllSessions($target->id);

        return $updated;
    }

    public function setRole(UserDto $actor, int $id, UserRole $role): UserDto
    {
        $target = $this->find($id);

        if (!$this->policy->canChangeRole($actor, $target->id)) {
            throw new ForbiddenException('You cannot change your own role.');
        }

        $updated = $this->users->setRole($target->id, $role);
        $this->sessionRevocation->revokeAllSessions($target->id);

        return $updated;
    }
}
