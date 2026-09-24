<?php

declare(strict_types=1);

use App\Controller\AdminUserController;
use App\Controller\AuthController;
use App\Controller\ProfileController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\RequireAdminMiddleware;

/** @var App\Http\Router $router */

$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/verify-email', [AuthController::class, 'verifyEmail']);

$router->get('/api/auth/profile', [AuthController::class, 'me'])->middleware(AuthMiddleware::class);
$router->get('/api/users/me', [AuthController::class, 'me'])->middleware(AuthMiddleware::class);

$router->patch('/api/profile', [ProfileController::class, 'update'])->middleware(AuthMiddleware::class);
$router->post('/api/profile/password', [ProfileController::class, 'changePassword'])->middleware(AuthMiddleware::class);
$router->delete('/api/profile', [ProfileController::class, 'destroy'])->middleware(AuthMiddleware::class);

$router->get('/api/admin/users', [AdminUserController::class, 'index'])
    ->middleware(AuthMiddleware::class, RequireAdminMiddleware::class);
$router->get('/api/admin/users/{id}', [AdminUserController::class, 'show'])
    ->middleware(AuthMiddleware::class, RequireAdminMiddleware::class);
$router->patch('/api/admin/users/{id}', [AdminUserController::class, 'update'])
    ->middleware(AuthMiddleware::class, RequireAdminMiddleware::class);
$router->post('/api/admin/users/{id}/ban', [AdminUserController::class, 'ban'])
    ->middleware(AuthMiddleware::class, RequireAdminMiddleware::class);
$router->post('/api/admin/users/{id}/role', [AdminUserController::class, 'setRole'])
    ->middleware(AuthMiddleware::class, RequireAdminMiddleware::class);
