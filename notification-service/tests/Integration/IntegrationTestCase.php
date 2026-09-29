<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;


abstract class IntegrationTestCase extends TestCase
{
    protected static function mongoUri(): string
    {
        return getenv('TEST_MONGO_URI') ?: 'mongodb://127.0.0.1:27017';
    }

    protected static function mongoDatabase(): string
    {
        return getenv('TEST_MONGO_DATABASE') ?: 'notifications_test';
    }

    protected static function rabbitMqHost(): string
    {
        return getenv('TEST_RABBITMQ_HOST') ?: '127.0.0.1';
    }

    protected static function rabbitMqPort(): int
    {
        return (int) (getenv('TEST_RABBITMQ_PORT') ?: 5672);
    }

    protected static function rabbitMqUser(): string
    {
        return getenv('TEST_RABBITMQ_USER') ?: 'guest';
    }

    protected static function rabbitMqPassword(): string
    {
        return getenv('TEST_RABBITMQ_PASSWORD') ?: 'guest';
    }

    protected static function mailhogHost(): string
    {
        return getenv('TEST_MAILHOG_HOST') ?: '127.0.0.1';
    }

    protected static function mailhogSmtpPort(): int
    {
        return (int) (getenv('TEST_MAILHOG_SMTP_PORT') ?: 1025);
    }

    protected static function mailhogUrl(): string
    {
        return getenv('TEST_MAILHOG_URL') ?: 'http://127.0.0.1:8025';
    }

    protected static function skipUnlessReachable(string $host, int $port, string $serviceName): void
    {
        $connection = @fsockopen($host, $port, $errno, $errstr, 1.0);

        if ($connection === false) {
            self::markTestSkipped("{$serviceName} is not reachable at {$host}:{$port} ({$errstr}) — skipping.");
        }

        fclose($connection);
    }

    protected static function parseHostPort(string $uri, int $defaultPort): array
    {
        $parts = parse_url($uri);

        return [$parts['host'] ?? '127.0.0.1', $parts['port'] ?? $defaultPort];
    }
}
