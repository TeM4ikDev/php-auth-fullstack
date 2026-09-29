<?php

declare(strict_types=1);

namespace App\Service\Exception;

use App\Http\Exception\HttpException;

final class NotReplayableException extends HttpException
{
    public function __construct(string $message = 'Only failed notifications can be replayed.')
    {
        parent::__construct(422, $message);
    }
}
