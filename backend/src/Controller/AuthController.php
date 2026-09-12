<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\LoginDto;
use App\Dto\RegisterDto;
use App\Middleware\AuthMiddleware;
use App\Service\AuthService;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class AuthController
{
    private $auth;
    private $middleware;

    public function __construct(?AuthService $auth = null, ?AuthMiddleware $middleware = null)
    {
        $this->auth = $auth;
        $this->middleware = $middleware ?? new AuthMiddleware();
    }

    public function register(): array
    {
        try {
            $dto = RegisterDto::fromArray($this->body());

            return ['status' => 201, 'body' => $this->auth()->register($dto)];
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->fail($e);
        }
    }

    public function login(): array
    {
        try {
            $dto = LoginDto::fromArray($this->body());

            return ['status' => 200, 'body' => $this->auth()->login($dto)];
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->fail($e);
        }
    }

    public function me(): array
    {
        try {
            $user = $this->middleware->user();

            if ($user === null) {
                return $this->error('Требуется авторизация', 401);
            }

            return ['status' => 200, 'body' => $this->auth()->publicUser($user)];
        } catch (Throwable $e) {
            return $this->fail($e);
        }
    }

    private function auth(): AuthService
    {
        if ($this->auth === null) {
            $this->auth = new AuthService();
        }

        return $this->auth;
    }

    private function body(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode(is_string($raw) ? $raw : '', true);

        return is_array($data) ? $data : [];
    }

    private function error(string $message, int $status): array
    {
        return ['status' => $status, 'body' => ['error' => $message]];
    }

    private function fail(Throwable $e): array
    {
        if ($e instanceof RuntimeException) {
            $code = (int) $e->getCode();

            if ($code >= 400 && $code < 600) {
                return $this->error($e->getMessage(), $code);
            }
        }

        error_log((string) $e);

        return $this->error('Внутренняя ошибка сервера', 500);
    }
}
