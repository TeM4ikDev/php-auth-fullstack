<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Config\JwtConfig;
use App\Enum\TokenType;
use App\Enum\UserRole;
use App\Service\Exception\InvalidTokenException;
use App\Service\JwtService;
use Firebase\JWT\JWT;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class JwtServiceTest extends TestCase
{
    private const SECRET = 'unit-test-secret-0123456789abcdef0123456789';

    #[Test]
    public function it_decodes_a_valid_access_token_issued_by_auth_service(): void
    {
        $payload = $this->service()->decode($this->issue([]));

        self::assertSame(1, $payload->userId);
        self::assertSame('artem@example.com', $payload->email);
        self::assertSame(UserRole::Analyst, $payload->role);
        self::assertSame('session-1', $payload->sessionId);
        self::assertSame(TokenType::Access, $payload->type);
        self::assertSame('jti-1', $payload->jti);
    }

    #[Test]
    public function it_rejects_a_token_signed_with_another_secret(): void
    {
        $token = JWT::encode($this->claims([]), 'a-different-secret-0123456789abcdef01234', 'HS256');

        $this->expectException(InvalidTokenException::class);

        $this->service()->decode($token);
    }

    #[Test]
    public function it_rejects_an_expired_token(): void
    {
        $token = $this->issue(['exp' => time() - 10]);

        $this->expectException(InvalidTokenException::class);

        $this->service()->decode($token);
    }

    #[Test]
    public function it_rejects_a_token_missing_required_claims(): void
    {
        $token = JWT::encode(['sub' => 1, 'exp' => time() + 60], self::SECRET, 'HS256');

        $this->expectException(InvalidTokenException::class);

        $this->service()->decode($token);
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

    private function service(): JwtService
    {
        return new JwtService(new JwtConfig(self::SECRET));
    }

    private function issue(array $overrides): string
    {
        return JWT::encode($this->claims($overrides), self::SECRET, 'HS256');
    }

    private function claims(array $overrides): array
    {
        return array_merge([
            'sub' => 1,
            'email' => 'artem@example.com',
            'role' => UserRole::Analyst->value,
            'jti' => 'jti-1',
            'sid' => 'session-1',
            'typ' => TokenType::Access->value,
            'iat' => time(),
            'exp' => time() + 900,
        ], $overrides);
    }
}
