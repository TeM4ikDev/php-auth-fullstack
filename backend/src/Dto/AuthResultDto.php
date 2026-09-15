<?php

declare(strict_types=1);

namespace App\Dto;

final class AuthResultDto
{
    public function __construct(
        public readonly string $token,
        public readonly UserDto $user,
    ) {
    }
}
