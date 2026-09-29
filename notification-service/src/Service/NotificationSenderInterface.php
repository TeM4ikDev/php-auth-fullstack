<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateNotificationDto;

interface NotificationSenderInterface
{
    public function send(CreateNotificationDto $dto): void;
}
