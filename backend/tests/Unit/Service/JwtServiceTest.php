<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Config\JwtConfig;
use App\Dto\UserDto;
use App\Enum\TokenType;
use App\Enum\UserRole;
use App\Service\Exception\InvalidTokenException;
use App\Service\JwtService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class JwtServiceTest extends TestCase
{
    // firebase/php-jwt требует ключ не короче 32 байт для HS256
    private const SECRET = 'unit-test-secret-0123456789abcdef0123456789';

    private const SESSION_ID = 'session-abc';

    #[Test]
    public function it_round_trips_claims_in_an_access_token(): void
    {
        $token = $this->service()->issueAccess($this->user(), self::SESSION_ID);
        $payload = $this->service()->decode($token);

        self::assertSame(1, $payload->userId);
        self::assertSame('artem@example.com', $payload->email);
        self::assertSame(UserRole::Analyst, $payload->role);
        self::assertSame(self::SESSION_ID, $payload->sessionId);
        self::assertSame(TokenType::Access, $payload->type);
        self::assertNotSame('', $payload->jti);
    }

    #[Test]
    public function access_and_refresh_tokens_carry_different_ttl_and_type(): void
    {
        $service = $this->service(accessTtl: 3600, refreshTtl: 2_592_000);

        $access = $service->decode($service->issueAccess($this->user(), self::SESSION_ID));
        $refresh = $service->decode($service->issueRefresh($this->user(), self::SESSION_ID));

        self::assertSame(TokenType::Access, $access->type);
        self::assertSame(TokenType::Refresh, $refresh->type);
        self::assertEqualsWithDelta(time() + 3600, $access->expiresAt, 2);
        self::assertEqualsWithDelta(time() + 2_592_000, $refresh->expiresAt, 2);
    }

    #[Test]
    public function it_rejects_a_tampered_signature(): void
    {
        $token = $this->service()->issueAccess($this->user(), self::SESSION_ID);

        $this->expectException(InvalidTokenException::class);

        $this->service()->decode(substr($token, 0, -2) . 'xx');
    }

    #[Test]
    public function it_rejects_a_token_signed_with_another_secret(): void
    {
        $foreign = new JwtService(new JwtConfig('a-different-secret-0123456789abcdef01234', 3600, 2_592_000));

        $this->expectException(InvalidTokenException::class);

        $this->service()->decode($foreign->issueAccess($this->user(), self::SESSION_ID));
    }

    #[Test]
    public function it_rejects_an_expired_token(): void
    {
        $expired = new JwtService(new JwtConfig(self::SECRET, -10, -10));

        $this->expectException(InvalidTokenException::class);

        $this->service()->decode($expired->issueAccess($this->user(), self::SESSION_ID));
    }

    #[Test]
    public function it_rejects_malformed_input(): void
    {
        $service = $this->service();

        foreach (['not-a-token', 'only.two', ''] as $malformed) {
            try {
                $service->decode($malformed);
                self::fail("Expected InvalidTokenException for [{$malformed}].");
            } catch (InvalidTokenException $e) {
                self::assertInstanceOf(InvalidTokenException::class, $e);
            }
        }
    }

    private function service(int $accessTtl = 3600, int $refreshTtl = 2_592_000): JwtService
    {
        return new JwtService(new JwtConfig(self::SECRET, $accessTtl, $refreshTtl));
    }

    private function user(): UserDto
    {
        return new UserDto(
            1,
            'Artem',
            null,
            'artem@example.com',
            'irrelevant-hash',
            UserRole::Analyst,
            false,
            '2026-01-01',
            null,
            '2026-01-01',
            null,
        );
    }
}
