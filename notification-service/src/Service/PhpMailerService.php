<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\MailConfig;
use PHPMailer\PHPMailer\PHPMailer;

final class PhpMailerService implements MailerInterface
{
    public function __construct(private readonly MailConfig $config)
    {
    }

    public function send(string $to, string $subject, string $body): void
    {
        $mailer = new PHPMailer(true);

        $mailer->isSMTP();
        $mailer->Host = $this->config->host;
        $mailer->Port = $this->config->port;
        $mailer->SMTPAuth = false;


        $mailer->SMTPAutoTLS = false;

        $mailer->setFrom($this->config->fromAddress, $this->config->fromName);

        $mailer->addAddress($to);
        $mailer->Subject = $subject;
        $mailer->Body = $body;
        $mailer->CharSet = PHPMailer::CHARSET_UTF8;

        $mailer->send();
    }
}
