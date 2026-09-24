<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class UserListDto
{
    /** @param list<UserDto> $items */
    public function __construct(
        public array $items,
        public int $total,
    ) {
    }
}
