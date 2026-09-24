<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\NotificationDto;
use App\Dto\NotificationListDto;
use App\Http\Exception\NotFoundException;
use App\Repository\NotificationRepositoryInterface;

final class NotificationQueryService
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications)
    {
    }

    public function list(int $page, int $perPage, array $filters): NotificationListDto
    {
        $offset = ($page - 1) * $perPage;

        return new NotificationListDto(
            $this->notifications->findAll($perPage, $offset, $filters),
            $this->notifications->countAll($filters),
        );
    }

    public function find(string $id): NotificationDto
    {
        $notification = $this->notifications->findById($id);

        if ($notification === null) {
            throw new NotFoundException(sprintf('Notification [%s] not found.', $id));
        }

        return $notification;
    }
}
