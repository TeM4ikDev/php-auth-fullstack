<?php

declare(strict_types=1);

namespace App\Service;

use App\Config\Config;

final class JwtService
{
    private const ALGORITHM = 'HS256';

    public function encode(array $claims): string
    {
        $issuedAt = time();

        $payload = array_merge($claims, [
            'iat' => $issuedAt,
            'exp' => $issuedAt + Config::jwtTtl(),
        ]);

        $header = $this->base64UrlEncode($this->json(['alg' => self::ALGORITHM, 'typ' => 'JWT']));
        $body = $this->base64UrlEncode($this->json($payload));
        $signature = $this->sign($header . '.' . $body);

        return $header . '.' . $body . '.' . $signature;
    }

    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $body, $signature] = $parts;

        if (!hash_equals($this->sign($header . '.' . $body), $signature)) {
            return null;
        }

        $decodedHeader = json_decode($this->base64UrlDecode($header), true);

        if (!is_array($decodedHeader) || ($decodedHeader['alg'] ?? '') !== self::ALGORITHM) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($body), true);

        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && time() >= (int) $payload['exp']) {
            return null;
        }

        return $payload;
    }

    private function sign(string $data): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $data, Config::jwtSecret(), true));
    }

    private function json(array $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }
}
