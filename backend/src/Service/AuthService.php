<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\AuthResultDto;
use App\Dto\CreateUserDto;
use App\Dto\LoginDto;
use App\Dto\RegisterDto;
use App\Dto\UserDto;
use App\Repository\UserRepositoryInterface;
use App\Service\Exception\InvalidCredentialsException;

final class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordService $password,
        private readonly JwtService $jwt,
    ) {
    }

    public function register(RegisterDto $dto): AuthResultDto
    {
        $user = $this->users->create(new CreateUserDto($dto->name, $dto->email, $this->password->hash($dto->password)));

        return $this->issueToken($user);
    }

    public function login(LoginDto $dto): AuthResultDto
    {
        $user = $this->users->findByEmail($dto->email);

        if ($user === null || !$this->password->verify($dto->password, $user->passwordHash)) {
            throw new InvalidCredentialsException();
        }

        return $this->issueToken($user);
    }

    public function findById(int $id): ?UserDto
    {
        return $this->users->findById($id);
    }

    private function issueToken(UserDto $user): AuthResultDto
    {
        $token = $this->jwt->encode(['sub' => $user->id, 'email' => $user->email]);

        return new AuthResultDto($token, $user);
    }
}
