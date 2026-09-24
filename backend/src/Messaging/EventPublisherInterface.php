<?php

declare(strict_types=1);

namespace App\Messaging;

interface EventPublisherInterface
{
    /** @param array<string, mixed> $payload */
    public function publish(string $routingKey, array $payload): void;
}
