<?php

declare(strict_types=1);

namespace App\Service\Exception;

use App\Http\Exception\HttpException;

final class InvalidCredentialsException extends HttpException
{
    public function __construct()
    {
        parent::__construct(401, 'Incorrect email or password.');
    }
}
