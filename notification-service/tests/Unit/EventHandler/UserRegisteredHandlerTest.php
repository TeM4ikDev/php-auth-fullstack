<?php

declare(strict_types=1);

namespace Tests\Unit\EventHandler;

use App\Dto\CreateNotificationDto;
use App\EventHandler\UserRegisteredHandler;
use App\Service\NotificationProcessorInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UserRegisteredHandlerTest extends TestCase
{
    #[Test]
    public function it_builds_an_email_notification_from_the_payload_and_forwards_attempt_and_id(): void
    {
        $payload = ['id' => 1111, 'name' => 'Art', 'email' => 'test@example.com'];

        $processor = $this->createMock(NotificationProcessorInterface::class);
        $processor->expects(self::once())
            ->method('process')
            ->with(
                self::callback(static function (CreateNotificationDto $dto) use ($payload): bool {
                    return $dto->event === 'user.registered'
                        && $dto->recipient === 'artem@example.com'
                        && $dto->payload === $payload;
                }),
                3,
                'existing-id',
            );

        (new UserRegisteredHandler($processor))->handle($payload, 3, 'existing-id');
    }
}
