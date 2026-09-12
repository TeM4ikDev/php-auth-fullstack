<?php

declare(strict_types=1);

use App\Controller\AuthController;
use App\Database\Database;
use App\Service\AuthService;
use App\Service\JwtService;
use App\Service\PasswordService;
use App\Service\RateLimiterService;

require_once __DIR__ . '/../vendor/autoload.php';

$send = static function (int $status, array $body): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

$allowedOrigins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $pdo = Database::connect();
    $passwordService = new PasswordService();
    $jwtService = new JwtService();
    $authService = new AuthService($pdo, $passwordService, $jwtService);

    $controller = new AuthController($authService);
} catch (Throwable $e) {
    $send(500, ['error' => 'err: ' . $e->getMessage()]);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($path, '/') ?: '/';

$routes = [
    '/api/auth/register' => ['POST' => [$controller, 'register']],
    '/api/auth/login'    => ['POST' => [$controller, 'login']],
    '/api/auth/profile'  => ['GET'  => [$controller, 'me']],
    '/api/users/me'      => ['GET'  => [$controller, 'me']],
];

$authRoutes = ['/api/auth/login', '/api/auth/register'];
$limit = in_array($path, $authRoutes, true) ? 5 : 60;
$window = 60;

$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate = (new RateLimiterService())->hit($clientIp . '|' . $path, $limit, $window);

header('X-RateLimit-Limit: ' . $limit);
header('X-RateLimit-Remaining: ' . $rate['remaining']);

if (!$rate['allowed']) {
    header('Retry-After: ' . $rate['retryAfter']);
    $send(429, ['error' => 'Слишком много запросов, попробуйте позже']);
}

if (!isset($routes[$path])) {
    $send(404, ['error' => 'Маршрут не найден', 'path' => $path]);
}

if (!isset($routes[$path][$method])) {
    header('Allow: ' . implode(', ', array_keys($routes[$path])));
    $send(405, ['error' => 'Метод не поддерживается']);
}

try {
    $response = $routes[$path][$method]();
    $send($response['status'] ?? 200, $response['body'] ?? []);
} catch (InvalidArgumentException $e) {
    $send(422, ['error' => $e->getMessage()]);
} catch (Throwable $e) {
    $send(500, ['error' => 'Внутренняя ошибка сервера']);
}