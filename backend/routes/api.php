<?php

declare(strict_types=1);

use App\Controller\AuthController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Router;

$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login', [AuthController::class, 'login']);

$router->get('/api/auth/profile', [AuthController::class, 'me'])->middleware(AuthMiddleware::class);
$router->get('/api/users/me', [AuthController::class, 'me'])->middleware(AuthMiddleware::class);
