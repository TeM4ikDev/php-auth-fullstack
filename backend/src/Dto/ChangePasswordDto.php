<?php

declare(strict_types=1);

namespace App\Dto;

use InvalidArgumentException;

final class ChangePasswordDto
{
    public const MIN_PASSWORD_LENGTH = 8;

    private function __construct(
        public readonly string $currentPassword,
        public readonly string $newPassword,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $currentPassword = is_string($data['currentPassword'] ?? null) ? $data['currentPassword'] : '';
        $newPassword = is_string($data['newPassword'] ?? null) ? $data['newPassword'] : '';

        if ($currentPassword === '') {
            throw new InvalidArgumentException('Current password is required.');
        }

        if (strlen($newPassword) < self::MIN_PASSWORD_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Password must be at least %d characters long.', self::MIN_PASSWORD_LENGTH),
            );
        }

        if ($currentPassword === $newPassword) {
            throw new InvalidArgumentException('The new password must differ from the current one.');
        }

        return new self($currentPassword, $newPassword);
    }
}
