<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\AppConfig;
use App\Dto\AuthResultDto;
use App\Dto\CreateUserDto;
use App\Dto\LoginDto;
use App\Dto\RegisterDto;
use App\Dto\UserDto;
use App\Http\Exception\ForbiddenException;
use App\Messaging\EventPublisherInterface;
use App\Repository\UserRepositoryInterface;
use App\Service\Exception\EmailNotVerifiedException;
use App\Service\Exception\InvalidCredentialsException;
use App\Service\Exception\InvalidVerificationTokenException;
use Throwable;

final class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordService $password,
        private readonly JwtService $jwt,
        private readonly MailerInterface $mailer,
        private readonly EventPublisherInterface $eventPublisher,
        private readonly AppConfig $appConfig,
    ) {
    }

    /** Регистрация не выдаёт токен — вход открыт только после подтверждения email. */
    public function register(RegisterDto $dto): UserDto
    {
        $verificationToken = bin2hex(random_bytes(32));

        $user = $this->users->create(new CreateUserDto(
            $dto->name,
            $dto->email,
            $this->password->hash($dto->password),
            $verificationToken,
            $dto->phone,
        ));

        $this->sendVerificationEmail($user, $verificationToken);
        $this->publishUserRegistered($user);

        return $user;
    }

    public function login(LoginDto $dto): AuthResultDto
    {
        $user = $this->users->findByEmail($dto->email);

        if ($user === null || !$this->password->verify($dto->password, $user->passwordHash)) {
            throw new InvalidCredentialsException();
        }

        if ($user->banned) {
            throw new ForbiddenException('Your account has been banned.');
        }

        if (!$user->isEmailVerified()) {
            throw new EmailNotVerifiedException();
        }

        return $this->issueToken($user);
    }

    public function verifyEmail(string $token): UserDto
    {
        $user = $this->users->findByVerificationToken($token);

        if ($user === null) {
            throw new InvalidVerificationTokenException();
        }

        return $this->users->markEmailVerified($user->id);
    }

    public function findById(int $id): ?UserDto
    {
        return $this->users->findById($id);
    }

    /**
     * Письмо — best-effort: временная недоступность Mailhog не должна ронять регистрацию,
     * только логируется. Это единственное место, где ошибка сознательно гасится.
     */
    private function sendVerificationEmail(UserDto $user, string $token): void
    {
        $link = sprintf('%s/verify-email?token=%s', rtrim($this->appConfig->clientUrl, '/'), $token);
        $body = "Hi {$user->name},\n\nPlease confirm your email by opening the link below:\n{$link}\n\n"
            . "If you did not create an account, you can ignore this email.";

        try {
            $this->mailer->send($user->email, 'Confirm your email', $body);
        } catch (Throwable $e) {
            error_log('Failed to send verification email: ' . $e->getMessage());
        }
    }

    /** Тот же принцип: очередь недоступна — регистрация всё равно должна завершиться успешно. */
    private function publishUserRegistered(UserDto $user): void
    {
        try {
            $this->eventPublisher->publish('user.registered', [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
        } catch (Throwable $e) {
            error_log('Failed to publish user.registered event: ' . $e->getMessage());
        }
    }

    private function issueToken(UserDto $user): AuthResultDto
    {
        $token = $this->jwt->encode([
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role->value,
        ]);

        return new AuthResultDto($token, $user);
    }
}
