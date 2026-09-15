<?php

declare(strict_types=1);

namespace App\Http\Exception;

final class MethodNotAllowedException extends HttpException
{
    public function __construct(array $allowedMethods)
    {
        parent::__construct(405, 'Method not allowed.', ['Allow' => implode(', ', $allowedMethods)]);
    }
}
