<?php

declare(strict_types=1);

namespace App\Repository\Exception;

use App\Http\Exception\HttpException;

final class EmailAlreadyTakenException extends HttpException
{
    public function __construct(string $email)
    {
        parent::__construct(409, sprintf('Email [%s] is already used.', $email));
    }
}
