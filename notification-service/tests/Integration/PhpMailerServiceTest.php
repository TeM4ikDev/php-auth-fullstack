<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\MailConfig;
use App\Service\PhpMailerService;
use PHPUnit\Framework\Attributes\Test;

final class PhpMailerServiceTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::skipUnlessReachable(self::mailhogHost(), self::mailhogSmtpPort(), 'Mailhog SMTP');
    }

    #[Test]
    public function it_delivers_an_email_that_mailhog_actually_receives(): void
    {
        $mailer = new PhpMailerService(new MailConfig(
            self::mailhogHost(),
            self::mailhogSmtpPort(),
            'no-reply@notification-service.test',
            'Notification Service Test',
        ));

        $recipient = sprintf('phpunit-%s@example.com', bin2hex(random_bytes(4)));
        $subject = 'PHPUnit integration test ' . bin2hex(random_bytes(4));

        $mailer->send($recipient, $subject, 'Hello from the integration test.');

        self::assertTrue(
            $this->mailhogReceived($recipient, $subject),
            'Expected Mailhog to have received the test email.',
        );
    }

    private function mailhogReceived(string $recipient, string $subject): bool
    {
        $response = @file_get_contents(self::mailhogUrl() . '/api/v2/messages?limit=50');

        if ($response === false) {
            self::fail('Could not reach the Mailhog HTTP API.');
        }

        $data = json_decode($response, true);

        foreach ($data['items'] ?? [] as $item) {
            $to = $item['Content']['Headers']['To'][0] ?? '';
            $subjectHeader = $item['Content']['Headers']['Subject'][0] ?? '';

            if (str_contains($to, $recipient) && $subjectHeader === $subject) {
                return true;
            }
        }

        return false;
    }
}
