<?php

declare(strict_types=1);

namespace App\Service\Exception;

use App\Http\Exception\HttpException;

final class TokenRevokedException extends HttpException
{
    public function __construct(string $message = 'This token has been revoked.')
    {
        parent::__construct(401, $message);
    }
}
