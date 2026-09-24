<?php

declare(strict_types=1);

namespace App\Dto;

final class NotificationListDto
{
    public function __construct(
        public readonly array $items,
        public readonly int $total,
    ) {
    }
}
