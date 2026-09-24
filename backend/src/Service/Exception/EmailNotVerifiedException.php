<?php

declare(strict_types=1);

namespace App\Service\Exception;

use App\Http\Exception\HttpException;

final class EmailNotVerifiedException extends HttpException
{
    public function __construct(string $message = 'Please verify your email before logging in.')
    {
        parent::__construct(403, $message);
    }
}
