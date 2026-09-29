<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateNotificationDto;

interface NotificationProcessorInterface
{
    public function process(CreateNotificationDto $dto, int $attempt, ?string $notificationId): void;
}
