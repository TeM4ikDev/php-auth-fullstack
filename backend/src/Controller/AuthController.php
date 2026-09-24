<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\LoginDto;
use App\Dto\RegisterDto;
use App\Dto\UserDto;
use App\Dto\VerifyEmailDto;
use App\Http\Request;
use App\Http\Response;
use App\Http\ResponseFactory;
use App\Resource\AuthResource;
use App\Resource\UserResource;
use App\Service\AuthService;
use RuntimeException;

final class AuthController
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly ResponseFactory $response,
    ) {
    }

    public function register(Request $request): Response
    {
        $dto = RegisterDto::fromArray($request->json());
        $user = $this->auth->register($dto);

        return $this->response->created(UserResource::fromDto($user));
    }

    public function verifyEmail(Request $request): Response
    {
        $dto = VerifyEmailDto::fromArray($request->json());
        $user = $this->auth->verifyEmail($dto->token);

        return $this->response->ok(UserResource::fromDto($user));
    }

    public function login(Request $request): Response
    {
        $dto = LoginDto::fromArray($request->json());
        $result = $this->auth->login($dto);

        return $this->response->ok(AuthResource::fromDto($result));
    }

    public function me(Request $request): Response
    {
        $user = $request->attribute('user');

        if (!$user instanceof UserDto) {
            throw new RuntimeException('Route is missing AuthMiddleware.');
        }

        return $this->response->ok(UserResource::fromDto($user));
    }
}
