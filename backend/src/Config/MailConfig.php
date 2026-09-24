<?php

declare(strict_types=1);

namespace App\Config;

final class MailConfig
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $fromAddress,
        public readonly string $fromName,
    ) {
    }

    public static function fromConfig(Config $config): self
    {
        return new self(
            $config->get('MAIL_HOST', 'mailhog'),
            $config->getInt('MAIL_PORT', 1025),
            $config->get('MAIL_FROM_ADDRESS', 'no-reply@auth-service.local'),
            $config->get('MAIL_FROM_NAME', 'Auth Service'),
        );
    }
}
