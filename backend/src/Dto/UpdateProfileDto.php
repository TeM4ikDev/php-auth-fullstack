<?php

declare(strict_types=1);

namespace App\Dto;

use InvalidArgumentException;

final class UpdateProfileDto
{
    private function __construct(
        public readonly string $name,
        public readonly ?string $phone,
        public readonly string $email,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $name = is_string($data['name'] ?? null) ? trim($data['name']) : '';
        $email = is_string($data['email'] ?? null) ? trim($data['email']) : '';
        $phone = is_string($data['phone'] ?? null) ? trim($data['phone']) : '';

        if ($name === '') {
            throw new InvalidArgumentException('Name is required.');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Invalid email address.');
        }

        if ($phone !== '' && preg_match('/^\+?[\d\s()-]{5,32}$/', $phone) !== 1) {
            throw new InvalidArgumentException('Invalid phone number.');
        }

        return new self($name, $phone === '' ? null : $phone, strtolower($email));
    }
}
