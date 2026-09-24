<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Config\AppConfig;
use App\Config\JwtConfig;
use App\Dto\CreateUserDto;
use App\Dto\LoginDto;
use App\Dto\RegisterDto;
use App\Dto\UserDto;
use App\Enum\UserRole;
use App\Http\Exception\ForbiddenException;
use App\Messaging\EventPublisherInterface;
use App\Repository\UserRepositoryInterface;
use App\Service\AuthService;
use App\Service\Exception\InvalidCredentialsException;
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
        $payload = $jwt->decode($result->token);

        self::assertNotNull($payload);
        self::assertSame('ANALYST', $payload['role']);
        self::assertSame(1, $payload['sub']);
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

    private function service(UserRepositoryInterface $users, ?JwtService $jwt = null): AuthService
    {
        return new AuthService(
            $users,
            new PasswordService(),
            $jwt ?? $this->jwt(),
            $this->createStub(MailerInterface::class),
            $this->createStub(EventPublisherInterface::class),
            $this->appConfig(),
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
        return new JwtService(new JwtConfig('unit-test-secret', 3600));
    }

    private function appConfig(): AppConfig
    {
        return new AppConfig([], 60, 60, 5, [], 'http://localhost:8081');
    }
}
