<?php

declare(strict_types=1);

namespace App\Http;

final class ResponseFactory
{
    public function ok(mixed $data): Response
    {
        return Response::json($data, 200);
    }

    public function created(mixed $data): Response
    {
        return Response::json($data, 201);
    }

    public function noContent(): Response
    {
        return Response::noContent();
    }

    public function error(string $message, int $status, array $errors = []): Response
    {
        $payload = ['error' => $message];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return Response::json($payload, $status);
    }
}
