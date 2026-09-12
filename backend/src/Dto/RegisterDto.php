<?php

declare(strict_types=1);

namespace App\Dto;

use InvalidArgumentException;

final class RegisterDto
{
    public const MIN_PASSWORD_LENGTH = 8;

    public $name;
    public $email;
    public $password;

    private function __construct(string $name, string $email, string $password)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
    }

    public static function fromArray(array $data): self
    {
        $name = is_string($data['name'] ?? null) ? trim($data['name']) : '';
        $email = is_string($data['email'] ?? null) ? trim($data['email']) : '';
        $password = is_string($data['password'] ?? null) ? $data['password'] : '';

        if ($name === '') {
            throw new InvalidArgumentException('Write a name');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('invalid email');
        }

        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Password must be %d charts', self::MIN_PASSWORD_LENGTH)
            );
        }

        return new self($name, strtolower($email), $password);
    }
}
