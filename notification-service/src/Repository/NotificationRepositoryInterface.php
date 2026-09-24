<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\CreateNotificationDto;
use App\Dto\NotificationDto;

interface NotificationRepositoryInterface
{
    public function create(CreateNotificationDto $dto): NotificationDto;

    public function markSent(string $id): NotificationDto;

    public function markFailed(string $id): NotificationDto;

    public function findById(string $id): ?NotificationDto;

    public function findAll(int $limit, int $offset, array $filters): array;

    public function countAll(array $filters): int;
}
