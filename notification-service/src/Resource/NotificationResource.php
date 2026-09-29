<?php

declare(strict_types=1);

namespace App\Resource;

use App\Dto\NotificationDto;
use App\Dto\NotificationListDto;

final class NotificationResource
{
    public static function fromDto(NotificationDto $notification): array
    {
        return [
            'id' => $notification->id,
            'event' => $notification->event,
            'channel' => $notification->channel->value,
            'recipient' => $notification->recipient,
            'payload' => $notification->payload,
            'status' => $notification->status->value,
            'attempts' => $notification->attempts,
            'createdAt' => $notification->createdAt,
            'updatedAt' => $notification->updatedAt,
            'sentAt' => $notification->sentAt,
            'lastError' => $notification->lastError,
            'deadLettered' => $notification->deadLettered,
        ];
    }

    public static function collection(NotificationListDto $list): array
    {
        return [
            'data' => array_map(static fn (NotificationDto $n): array => self::fromDto($n), $list->items),
            'total' => $list->total,
        ];
    }
}
