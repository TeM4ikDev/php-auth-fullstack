<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\LoginDto;
use App\Dto\RefreshTokenRequestDto;
use App\Dto\RegisterDto;
use App\Dto\TokenPayloadDto;
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

    public function refresh(Request $request): Response
    {
        $dto = RefreshTokenRequestDto::fromArray($request->json());
        $result = $this->auth->refresh($dto->refreshToken);

        return $this->response->ok(AuthResource::fromDto($result));
    }

    public function logout(Request $request): Response
    {
        $body = $request->json();
        $refreshToken = is_string($body['refreshToken'] ?? null) ? trim($body['refreshToken']) : '';

        $this->auth->logout($this->tokenPayload($request), $refreshToken !== '' ? $refreshToken : null);

        return $this->response->noContent();
    }

    public function me(Request $request): Response
    {
        $user = $request->attribute('user');

        if (!$user instanceof UserDto) {
            throw new RuntimeException('Route is missing AuthMiddleware.');
        }

        return $this->response->ok(UserResource::fromDto($user));
    }

    private function tokenPayload(Request $request): TokenPayloadDto
    {
        $payload = $request->attribute('tokenPayload');

        if (!$payload instanceof TokenPayloadDto) {
            throw new RuntimeException('Route is missing AuthMiddleware.');
        }

        return $payload;
    }
}
