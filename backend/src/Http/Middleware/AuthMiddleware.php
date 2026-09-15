<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Exception\UnauthenticatedException;
use App\Http\Request;
use App\Http\Response;
use App\Service\AuthService;
use App\Service\JwtService;
use Closure;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly JwtService $jwt,
        private readonly AuthService $auth,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $payload = $token !== null ? $this->jwt->decode($token) : null;

        if ($payload === null || !isset($payload['sub'])) {
            throw new UnauthenticatedException();
        }

        $user = $this->auth->findById((int) $payload['sub']);

        if ($user === null) {
            throw new UnauthenticatedException();
        }

        return $next($request->withAttribute('user', $user));
    }
}
