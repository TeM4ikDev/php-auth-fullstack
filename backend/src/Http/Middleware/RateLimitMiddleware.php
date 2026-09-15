<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Config\AppConfig;
use App\Http\Exception\TooManyRequestsException;
use App\Http\Request;
use App\Http\Response;
use App\Service\RateLimiterService;
use Closure;

final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RateLimiterService $limiter,
        private readonly AppConfig $config,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $isStrict = in_array($request->path, $this->config->rateLimitStrictPaths, true);
        $limit = $isStrict ? $this->config->rateLimitStrictLimit : $this->config->rateLimitDefaultLimit;

        $result = $this->limiter->hit(
            $request->ip() . '|' . $request->path,
            $limit,
            $this->config->rateLimitWindowSeconds,
        );

        if (!$result->allowed) {
            throw new TooManyRequestsException($result->retryAfterSeconds);
        }

        return $next($request)
            ->withHeader('X-RateLimit-Limit', (string) $limit)
            ->withHeader('X-RateLimit-Remaining', (string) $result->remaining);
    }
}
