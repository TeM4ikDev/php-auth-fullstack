<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ChangePasswordDto;
use App\Dto\UpdateProfileDto;
use App\Dto\UserDto;
use App\Repository\UserRepositoryInterface;
use App\Service\Exception\WrongCurrentPasswordException;

final class ProfileService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordService $password,
    ) {
    }

    public function update(UserDto $user, UpdateProfileDto $dto): UserDto
    {
        return $this->users->update($user->id, $dto);
    }

    public function changePassword(UserDto $user, ChangePasswordDto $dto): void
    {
        if (!$this->password->verify($dto->currentPassword, $user->passwordHash)) {
            throw new WrongCurrentPasswordException();
        }

        $this->users->updatePassword($user->id, $this->password->hash($dto->newPassword));
    }

    public function delete(UserDto $user): void
    {
        $this->users->softDelete($user->id);
    }
}
