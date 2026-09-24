<?php

declare(strict_types=1);

namespace App\Http\Exception;

final class ValidationException extends HttpException
{
    public function __construct(string $message)
    {
        parent::__construct(422, $message);
    }
}
