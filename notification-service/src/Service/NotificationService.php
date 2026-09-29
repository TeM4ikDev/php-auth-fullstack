<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateNotificationDto;

final class NotificationService implements NotificationSenderInterface
{
    public function __construct(private readonly MailerInterface $mailer)
    {
    }

    /** Не глотает исключения — вызывающий (NotificationProcessor) должен отличать успех от сбоя. */
    public function send(CreateNotificationDto $dto): void
    {
        [$subject, $body] = $this->renderEmail($dto);

        $this->mailer->send($dto->recipient, $subject, $body);
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
