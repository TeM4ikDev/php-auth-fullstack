<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateNotificationDto;
use App\Dto\NotificationDto;
use App\Repository\NotificationRepositoryInterface;
use Throwable;

final class NotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notifications,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function recordAndSend(CreateNotificationDto $dto): NotificationDto
    {
        $notification = $this->notifications->create($dto);

        try {
            [$subject, $body] = $this->renderEmail($dto);
            $this->mailer->send($dto->recipient, $subject, $body);

            return $this->notifications->markSent($notification->id);
        } catch (Throwable $e) {
            error_log("Failed to send notification [{$notification->id}]: " . $e->getMessage());

            return $this->notifications->markFailed($notification->id);
        }
    }

    private function renderEmail(CreateNotificationDto $dto): array
    {
        return match ($dto->event) {
            'user.registered' => [
                'Welcome!',
                sprintf("Hi %s,\n\nYour account has been created successfully.", $dto->payload['name'] ?? 'there'),
            ],
            default => [$dto->event, json_encode($dto->payload, JSON_UNESCAPED_UNICODE)],
        };
    }
}
