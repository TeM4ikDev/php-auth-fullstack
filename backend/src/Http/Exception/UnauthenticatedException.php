<?php

declare(strict_types=1);

namespace App\Http\Exception;

final class UnauthenticatedException extends HttpException
{
    public function __construct(string $message = 'Authentication required.')
    {
        parent::__construct(401, $message);
    }
}
