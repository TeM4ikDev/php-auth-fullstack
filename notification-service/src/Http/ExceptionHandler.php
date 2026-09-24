<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Exception\HttpException;
use InvalidArgumentException;
use Throwable;

final class ExceptionHandler
{
    public function __construct(private readonly ResponseFactory $response)
    {
    }

    public function render(Throwable $e): Response
    {
        if ($e instanceof HttpException) {
            $response = $this->response->error($e->getMessage(), $e->statusCode());

            foreach ($e->headers() as $name => $value) {
                $response = $response->withHeader($name, $value);
            }

            return $response;
        }

        if ($e instanceof InvalidArgumentException) {
            return $this->response->error($e->getMessage(), 422);
        }

        error_log((string) $e);

        return $this->response->error('Internal server error.', 500);
    }
}
