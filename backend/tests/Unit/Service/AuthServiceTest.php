<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Config\AppConfig;
use App\Config\JwtConfig;
use App\Dto\CreateUserDto;
use App\Dto\LoginDto;
use App\Dto\RefreshTokenDto;
use App\Dto\RegisterDto;
use App\Dto\UserDto;
use App\Enum\UserRole;
use App\Http\Exception\ForbiddenException;
use App\Messaging\EventPublisherInterface;
use App\Repository\RefreshTokenRepositoryInterface;
use App\Repository\SessionRepositoryInterface;
use App\Repository\TokenBlacklistRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Service\AuthService;
use App\Service\Exception\InvalidCredentialsException;
use App\Service\Exception\InvalidRefreshTokenException;
use App\Service\JwtService;
use App\Service\MailerInterface;
use App\Service\PasswordService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    #[Test]
    public function registration_assigns_the_customer_role_and_hashes_the_password(): void
    {
        $password = new PasswordService();
        $users = $this->createMock(UserRepositoryInterface::class);

        $users->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (CreateUserDto $dto) use ($password): bool {
                return $dto->role === UserRole::Customer
                    && $dto->passwordHash !== 'secret123'
                    && $password->verify('secret123', $dto->passwordHash)
                    && $dto->emailVerificationToken !== '';
            }))
            ->willReturn($this->user(verified: false));

        $service = new AuthService(
            $users,
            $password,
            $this->jwt(),
            $this->createStub(MailerInterface::class),
            $this->createStub(EventPublisherInterface::class),
            $this->appConfig(),
            $this->createStub(SessionRepositoryInterface::class),
            $this->createStub(RefreshTokenRepositoryInterface::class),
            $this->createStub(TokenBlacklistRepositoryInterface::class),
        );

        $user = $service->register(RegisterDto::fromArray([
            'name' => 'Artem',
            'email' => 'artem@example.com',
            'password' => 'secret123',
        ]));

        self::assertSame('artem@example.com', $user->email);
    }

    #[Test]
    public function the_issued_token_carries_the_user_role(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($this->user(role: UserRole::Analyst));

        $jwt = $this->jwt();
        $service = $this->service($users, $jwt);

        $result = $service->login(LoginDto::fromArray(['email' => 'artem@example.com', 'password' => 'secret123']));
        $payload = $jwt->decode($result->accessToken);

        self::assertSame(UserRole::Analyst, $payload->role);
        self::assertSame(1, $payload->userId);
        self::assertSame($result->expiresIn, $jwt->accessTtlSeconds());
    }

    #[Test]
    public function login_fails_for_an_unknown_email(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn(null);

        $this->expectException(InvalidCredentialsException::class);

        $this->service($users)
            ->login(LoginDto::fromArray(['email' => 'nobody@example.com', 'password' => 'secret123']));
    }

    #[Test]
    public function login_fails_for_a_wrong_password(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($this->user());

        $this->expectException(InvalidCredentialsException::class);

        $this->service($users)
            ->login(LoginDto::fromArray(['email' => 'artem@example.com', 'password' => 'wrong-password']));
    }

    #[Test]
    public function a_banned_user_cannot_log_in(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($this->user(banned: true));

        $this->expectException(ForbiddenException::class);

        $this->service($users)
            ->login(LoginDto::fromArray(['email' => 'artem@example.com', 'password' => 'secret123']));
    }

    #[Test]
    public function refreshing_rotates_the_token_and_rejects_the_old_one(): void
    {
        $user = $this->user();
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($user);
        $users->method('findById')->willReturn($user);

        $refreshTokens = new InMemoryRefreshTokenRepository();
        $service = $this->service($users, refreshTokens: $refreshTokens);

        $issued = $service->login(LoginDto::fromArray(['email' => 'artem@example.com', 'password' => 'secret123']));
        $rotated = $service->refresh($issued->refreshToken);

        self::assertNotSame($issued->refreshToken, $rotated->refreshToken);

        $this->expectException(InvalidRefreshTokenException::class);

        $service->refresh($issued->refreshToken);
    }

    #[Test]
    public function logout_blacklists_the_access_token(): void
    {
        $blacklist = new InMemoryTokenBlacklistRepository();
        $service = $this->service($this->createStub(UserRepositoryInterface::class), blacklist: $blacklist);

        $jwt = $this->jwt();
        $payload = $jwt->decode($jwt->issueAccess($this->user(), 'session-1'));

        $service->logout($payload, null);

        self::assertTrue($blacklist->isRevoked($payload->jti));
    }

    private function service(
        UserRepositoryInterface $users,
        ?JwtService $jwt = null,
        ?SessionRepositoryInterface $sessions = null,
        ?RefreshTokenRepositoryInterface $refreshTokens = null,
        ?TokenBlacklistRepositoryInterface $blacklist = null,
    ): AuthService {
        return new AuthService(
            $users,
            new PasswordService(),
            $jwt ?? $this->jwt(),
            $this->createStub(MailerInterface::class),
            $this->createStub(EventPublisherInterface::class),
            $this->appConfig(),
            $sessions ?? $this->createStub(SessionRepositoryInterface::class),
            $refreshTokens ?? $this->createStub(RefreshTokenRepositoryInterface::class),
            $blacklist ?? $this->createStub(TokenBlacklistRepositoryInterface::class),
        );
    }

    private function user(UserRole $role = UserRole::Customer, bool $banned = false, bool $verified = true): UserDto
    {
        return new UserDto(
            1,
            'Artem',
            null,
            'artem@example.com',
            (new PasswordService())->hash('secret123'),
            $role,
            $banned,
            $verified ? '2026-01-01' : null,
            null,
            '2026-01-01',
            null,
        );
    }

    private function jwt(): JwtService
    {
        // firebase/php-jwt требует ключ не короче 32 байт для HS256
        return new JwtService(new JwtConfig('unit-test-secret-0123456789abcdef0123456789', 3600, 2_592_000));
    }

    private function appConfig(): AppConfig
    {
        return new AppConfig([], 60, 60, 5, [], 'http://localhost:8081');
    }
}

/** Лёгкая тестовая замена Redis — только то, что реально нужно ротации в тесте. */
final class InMemoryRefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    private array $tokens = [];

    public function store(string $refreshToken, int $userId, string $sessionId, int $ttlSeconds): void
    {
        $this->tokens[$refreshToken] = new RefreshTokenDto($userId, $sessionId);
    }

    public function findByToken(string $refreshToken): ?RefreshTokenDto
    {
        return $this->tokens[$refreshToken] ?? null;
    }

    public function delete(string $refreshToken): void
    {
        unset($this->tokens[$refreshToken]);
    }

    public function deleteForSession(int $userId, string $sessionId): void
    {
        foreach ($this->tokens as $token => $dto) {
            if ($dto->userId === $userId && $dto->sessionId === $sessionId) {
                unset($this->tokens[$token]);
            }
        }
    }
}

/** Лёгкая тестовая замена Redis для проверки logout(). */
final class InMemoryTokenBlacklistRepository implements TokenBlacklistRepositoryInterface
{
    private array $revoked = [];

    public function revoke(string $jti, int $ttlSeconds): void
    {
        $this->revoked[$jti] = true;
    }

    public function isRevoked(string $jti): bool
    {
        return isset($this->revoked[$jti]);
    }
}
