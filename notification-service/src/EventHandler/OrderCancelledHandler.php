<?php

declare(strict_types=1);

namespace App\EventHandler;

final class OrderCancelledHandler implements EventHandlerInterface
{
    public function handle(array $payload, int $attempt, ?string $notificationId): void
    {
        // Order Service не реализован в этом проекте — обработчик оставлен пустым
    }
}
