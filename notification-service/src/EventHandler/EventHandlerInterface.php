<?php

declare(strict_types=1);

namespace App\EventHandler;

interface EventHandlerInterface
{
    public function handle(array $payload, int $attempt, ?string $notificationId): void;
}
