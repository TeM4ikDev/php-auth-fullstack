<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RouteTest extends TestCase
{
    /** @param array<string, string>|null $expected */
    #[Test]
    #[DataProvider('paths')]
    public function it_matches_paths(string $routePath, string $requestPath, ?array $expected): void
    {
        self::assertSame($expected, (new Route('GET', $routePath, []))->match($requestPath));
    }

    public static function paths(): array
    {
        return [
            'static path matches' => ['/api/profile', '/api/profile', []],
            'static path is exact' => ['/api/profile', '/api/profiles', null],
            'placeholder captures the value' => ['/api/admin/users/{id}', '/api/admin/users/42', ['id' => '42']],
            'placeholder needs a segment' => ['/api/admin/users/{id}', '/api/admin/users', null],
            // Иначе /users/{id} перехватил бы /users/42/ban и роут бана стал бы недостижим
            'placeholder does not span segments' => ['/api/admin/users/{id}', '/api/admin/users/42/ban', null],
            'placeholder before a suffix' => ['/api/admin/users/{id}/ban', '/api/admin/users/7/ban', ['id' => '7']],
            'non numeric value is captured too' => ['/api/admin/users/{id}', '/api/admin/users/abc', ['id' => 'abc']],
        ];
    }

    #[Test]
    public function middleware_is_collected_in_order(): void
    {
        $route = (new Route('GET', '/api/profile', []))->middleware('First', 'Second');

        self::assertSame(['First', 'Second'], $route->middlewareStack());
    }
}
