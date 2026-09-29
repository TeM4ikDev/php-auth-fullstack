<?php

declare(strict_types=1);

namespace App\Service\Exception;

use App\Http\Exception\HttpException;

final class InvalidRefreshTokenException extends HttpException
{
    public function __construct(string $message = 'Invalid or expired refresh token.')
    {
        parent::__construct(401, $message);
    }
}
