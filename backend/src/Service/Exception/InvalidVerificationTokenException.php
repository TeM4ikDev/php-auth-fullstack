<?php

declare(strict_types=1);

namespace App\Service\Exception;

use App\Http\Exception\HttpException;

final class InvalidVerificationTokenException extends HttpException
{
    public function __construct(string $message = 'Invalid or already used verification token.')
    {
        parent::__construct(422, $message);
    }
}
