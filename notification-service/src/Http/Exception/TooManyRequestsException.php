<?php

declare(strict_types=1);

namespace App\Http\Exception;

final class TooManyRequestsException extends HttpException
{
    public function __construct(int $retryAfterSeconds)
    {
        parent::__construct(429, 'Too many requests.', ['Retry-After' => (string) $retryAfterSeconds]);
    }
}
