<?php

declare(strict_types=1);

namespace App\Dto;

use InvalidArgumentException;

final class RegisterDto
{
    public const MIN_PASSWORD_LENGTH = 8;

    private function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $name = is_string($data['name'] ?? null) ? trim($data['name']) : '';
        $email = is_string($data['email'] ?? null) ? trim($data['email']) : '';
        $password = is_string($data['password'] ?? null) ? $data['password'] : '';

        if ($name === '') {
            throw new InvalidArgumentException('Name is required.');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Invalid email address.');
        }

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Password must be at least %d characters long.', self::MIN_PASSWORD_LENGTH),
            );
        }

        return new self($name, strtolower($email), $password);
    }
}
