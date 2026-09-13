<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\AuthService;
use App\Service\JwtService;

final class AuthMiddleware
{
    private $jwt;
    private $auth;

    public function __construct(?JwtService $jwt = null, ?AuthService $auth = null)
    {
        $this->jwt = $jwt ?? new JwtService();
        $this->auth = $auth;
    }

    public function user(): ?array
    {
        $token = $this->bearerToken();

        if ($token === null) return null;

        $payload = $this->jwt->decode($token);

        if ($payload === null || !isset($payload['sub'])) return null;

        $auth = $this->auth ?? new AuthService();

        return $auth->findById((int) $payload['sub']);
    }

    private function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if ($header === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (!is_string($header) || stripos($header, 'Bearer ') !== 0) {
            return null;
        }

        $token = trim(substr($header, 7));

        return $token === '' ? null : $token;
    }
}
