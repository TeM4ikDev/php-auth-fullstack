<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Config\JwtConfig;
use App\Service\JwtService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class JwtServiceTest extends TestCase
{
    private const SECRET = 'unit-test-secret';

    #[Test]
    public function it_round_trips_claims(): void
    {
        $token = $this->service()->encode(['sub' => 42, 'role' => 'ADMIN']);
        $payload = $this->service()->decode($token);

        self::assertNotNull($payload);
        self::assertSame(42, $payload['sub']);
        self::assertSame('ADMIN', $payload['role']);
    }

    #[Test]
    public function it_adds_issued_at_and_expiry_claims(): void
    {
        $payload = $this->service()->decode($this->service()->encode(['sub' => 1]));

        self::assertNotNull($payload);
        self::assertArrayHasKey('iat', $payload);
        self::assertArrayHasKey('exp', $payload);
        self::assertSame($payload['iat'] + 3600, $payload['exp']);
    }

    #[Test]
    public function it_rejects_a_tampered_signature(): void
    {
        $token = $this->service()->encode(['sub' => 1]);

        self::assertNull($this->service()->decode(substr($token, 0, -2) . 'xx'));
    }

    #[Test]
    public function it_rejects_a_token_signed_with_another_secret(): void
    {
        $foreign = new JwtService(new JwtConfig('a-different-secret', 3600));

        self::assertNull($this->service()->decode($foreign->encode(['sub' => 1])));
    }

    #[Test]
    public function it_rejects_an_expired_token(): void
    {
        $expired = new JwtService(new JwtConfig(self::SECRET, -10));

        self::assertNull($this->service()->decode($expired->encode(['sub' => 1])));
    }

    #[Test]
    public function it_rejects_malformed_input(): void
    {
        $service = $this->service();

        self::assertNull($service->decode('not-a-token'));
        self::assertNull($service->decode('only.two'));
        self::assertNull($service->decode(''));
    }

    private function service(): JwtService
    {
        return new JwtService(new JwtConfig(self::SECRET, 3600));
    }
}
