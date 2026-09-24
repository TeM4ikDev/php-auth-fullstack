<?php

declare(strict_types=1);

namespace App\Service\Exception;

use App\Http\Exception\HttpException;

final class WrongCurrentPasswordException extends HttpException
{
    public function __construct(string $message = 'Current password is incorrect.')
    {
        parent::__construct(422, $message);
    }
}
