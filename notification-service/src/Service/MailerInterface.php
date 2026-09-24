<?php

declare(strict_types=1);

namespace App\Service;

interface MailerInterface
{
    public function send(string $to, string $subject, string $body): void;
}
