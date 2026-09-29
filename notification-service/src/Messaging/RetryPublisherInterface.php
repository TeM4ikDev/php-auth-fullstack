<?php

declare(strict_types=1);

namespace App\Messaging;

use App\Dto\RetryMessageDto;

interface RetryPublisherInterface
{
    public function scheduleRetry(RetryMessageDto $message): void;

    public function sendToDlq(RetryMessageDto $message): void;

    /** Реплей из DLQ — публикует заново в основной топик с исходным routing key. */
    public function republish(string $notificationId, string $event, array $payload): void;
}
