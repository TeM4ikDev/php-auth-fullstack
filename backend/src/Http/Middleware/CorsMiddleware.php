<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Config\AppConfig;
use App\Http\Request;
use App\Http\Response;
use Closure;

final class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly AppConfig $config)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $isPreflight = $request->isMethod('OPTIONS');
        $response = $isPreflight ? Response::noContent() : $next($request);

        $origin = $request->header('origin');

        if ($origin === null || !in_array($origin, $this->config->corsAllowedOrigins, true)) {
            return $response;
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Vary', 'Origin')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}
