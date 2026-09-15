<?php

declare(strict_types=1);

use App\Config\AppConfig;
use App\Config\Config;
use App\Config\DatabaseConfig;
use App\Config\DatabaseConfigFactory;
use App\Config\JwtConfig;
use App\Container\Container;
use App\Database\ConnectionFactory;
use App\Http\ResponseFactory;
use App\Repository\PdoUserRepository;
use App\Repository\UserRepositoryInterface;
use App\Service\RateLimiterService;

return static function (Container $container): void {
    $container->singleton(Config::class, static fn (): Config => new Config());

    $container->singleton(
        DatabaseConfig::class,
        static fn (Container $c): DatabaseConfig => $c->get(DatabaseConfigFactory::class)->create(),
    );

    $container->singleton(
        JwtConfig::class,
        static fn (Container $c): JwtConfig => JwtConfig::fromConfig($c->get(Config::class)),
    );

    $container->singleton(
        AppConfig::class,
        static fn (Container $c): AppConfig => (require __DIR__ . '/app.php')($c->get(Config::class)),
    );

    $container->singleton(PDO::class, static fn (Container $c): PDO => $c->get(ConnectionFactory::class)->create());

    $container->singleton(
        UserRepositoryInterface::class,
        static fn (Container $c): UserRepositoryInterface => new PdoUserRepository($c->get(PDO::class)),
    );

    $container->singleton(
        RateLimiterService::class,
        static fn (): RateLimiterService => new RateLimiterService(),
    );

    $container->singleton(ResponseFactory::class, static fn (): ResponseFactory => new ResponseFactory());
};
