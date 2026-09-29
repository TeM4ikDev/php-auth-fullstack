<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Dto\AuthenticatedUserDto;
use App\Enum\UserRole;
use App\Http\Exception\ForbiddenException;
use App\Http\Exception\UnauthenticatedException;
use App\Http\Request;
use App\Http\Response;
use Closure;

final class RequireAdminMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->attribute('user');

        if (!$user instanceof AuthenticatedUserDto) {
            throw new UnauthenticatedException();
        }

        if ($user->role !== UserRole::Admin) {
            throw new ForbiddenException();
        }

        return $next($request);
    }
}
