<?php

declare(strict_types=1);

use App\Controller\NotificationController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\RequireAdminOrAnalystMiddleware;

/** @var App\Http\Router $router */

$router->get('/api/notifications', [NotificationController::class, 'index'])
    ->middleware(AuthMiddleware::class, RequireAdminOrAnalystMiddleware::class);

$router->get('/api/notifications/{id}', [NotificationController::class, 'show'])
    ->middleware(AuthMiddleware::class, RequireAdminOrAnalystMiddleware::class);
