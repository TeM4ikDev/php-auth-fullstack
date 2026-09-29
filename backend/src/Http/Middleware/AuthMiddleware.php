<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enum\TokenType;
use App\Http\Exception\ForbiddenException;
use App\Http\Exception\UnauthenticatedException;
use App\Http\Request;
use App\Http\Response;
use App\Repository\TokenBlacklistRepositoryInterface;
use App\Service\AuthService;
use App\Service\Exception\InvalidTokenException;
use App\Service\Exception\TokenRevokedException;
use App\Service\JwtService;
use Closure;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly JwtService $jwt,
        private readonly AuthService $auth,
        private readonly TokenBlacklistRepositoryInterface $blacklist,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            throw new UnauthenticatedException();
        }

        $payload = $this->jwt->decode($token);

        if ($payload->type !== TokenType::Access) {
            throw new InvalidTokenException();
        }

        if ($this->blacklist->isRevoked($payload->jti)) {
            throw new TokenRevokedException();
        }

        $user = $this->auth->findById($payload->userId);

        if ($user === null) {
            throw new UnauthenticatedException();
        }

        // Бан проверяется на каждом запросе, поэтому действует сразу, не дожидаясь истечения токена
        if ($user->banned) {
            throw new ForbiddenException('Your account has been banned.');
        }

        return $next($request->withAttribute('user', $user)->withAttribute('tokenPayload', $payload));
    }
}
