<?php

declare(strict_types=1);

namespace App\Http\Exception;

final class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'Insufficient permissions.')
    {
        parent::__construct(403, $message);
    }
}
