<?php

declare(strict_types=1);

namespace App\Dto;

use InvalidArgumentException;

final class LoginDto
{
    public $email;

    public $password;

    private function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    public static function fromArray(array $data): self
    {
        $email = is_string($data['email'] ?? null) ? trim($data['email']) : '';
        $password = is_string($data['password'] ?? null) ? $data['password'] : '';

        if ($email === '' || $password === '') {
            throw new InvalidArgumentException('Please provide a valid email address');
        }

        return new self(strtolower($email), $password);
    }
}
