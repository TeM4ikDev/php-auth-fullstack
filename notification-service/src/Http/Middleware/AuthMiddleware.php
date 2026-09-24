<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Dto\AuthenticatedUserDto;
use App\Enum\UserRole;
use App\Http\Exception\UnauthenticatedException;
use App\Http\Request;
use App\Http\Response;
use App\Service\JwtService;
use Closure;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $payload = $token !== null ? $this->jwt->decode($token) : null;

        if ($payload === null || !isset($payload['sub'], $payload['email'], $payload['role'])) {
            throw new UnauthenticatedException();
        }

        $role = UserRole::tryFrom((string) $payload['role']);

        if ($role === null) {
            throw new UnauthenticatedException();
        }

        $user = new AuthenticatedUserDto((int) $payload['sub'], (string) $payload['email'], $role);

        return $next($request->withAttribute('user', $user));
    }
}
