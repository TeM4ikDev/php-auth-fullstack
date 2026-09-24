<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Dto\UserDto;
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

        if (!$user instanceof UserDto) {
            throw new UnauthenticatedException();
        }

        if (!$user->role->canManageUsers()) {
            throw new ForbiddenException();
        }

        return $next($request);
    }
}
