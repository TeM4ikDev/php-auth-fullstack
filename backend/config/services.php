<?php

declare(strict_types=1);

use App\Config\AppConfig;
use App\Config\Config;
use App\Config\DatabaseConfig;
use App\Config\DatabaseConfigFactory;
use App\Config\JwtConfig;
use App\Config\MailConfig;
use App\Config\RabbitMqConfig;
use App\Config\RedisConfig;
use App\Config\RedisConfigFactory;
use App\Container\Container;
use App\Database\ConnectionFactory;
use App\Http\ResponseFactory;
use App\Messaging\AmqpEventPublisher;
use App\Messaging\EventPublisherInterface;
use App\Redis\RedisConnectionFactory;
use App\Repository\PdoUserRepository;
use App\Repository\RedisRefreshTokenRepository;
use App\Repository\RedisSessionRepository;
use App\Repository\RedisTokenBlacklistRepository;
use App\Repository\RefreshTokenRepositoryInterface;
use App\Repository\SessionRepositoryInterface;
use App\Repository\TokenBlacklistRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use App\Service\AccessPolicy;
use App\Service\MailerInterface;
use App\Service\PhpMailerService;
use App\Service\RateLimiterInterface;
use App\Service\RedisRateLimiter;
use Predis\Client;

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
        RateLimiterInterface::class,
        static fn (Container $c): RateLimiterInterface => new RedisRateLimiter($c->get(Client::class)),
    );

    $container->singleton(AccessPolicy::class, static fn (): AccessPolicy => new AccessPolicy());

    $container->singleton(
        MailConfig::class,
        static fn (Container $c): MailConfig => MailConfig::fromConfig($c->get(Config::class)),
    );

    $container->singleton(
        RabbitMqConfig::class,
        static fn (Container $c): RabbitMqConfig => RabbitMqConfig::fromConfig($c->get(Config::class)),
    );

    $container->singleton(
        MailerInterface::class,
        static fn (Container $c): MailerInterface => new PhpMailerService($c->get(MailConfig::class)),
    );

    $container->singleton(
        EventPublisherInterface::class,
        static fn (Container $c): EventPublisherInterface => new AmqpEventPublisher($c->get(RabbitMqConfig::class)),
    );

    $container->singleton(
        RedisConfig::class,
        static fn (Container $c): RedisConfig => $c->get(RedisConfigFactory::class)->create(),
    );

    $container->singleton(Client::class, static fn (Container $c): Client => $c->get(RedisConnectionFactory::class)->create());

    $container->singleton(
        TokenBlacklistRepositoryInterface::class,
        static fn (Container $c): TokenBlacklistRepositoryInterface => new RedisTokenBlacklistRepository($c->get(Client::class)),
    );

    $container->singleton(
        SessionRepositoryInterface::class,
        static fn (Container $c): SessionRepositoryInterface => new RedisSessionRepository($c->get(Client::class)),
    );

    $container->singleton(
        RefreshTokenRepositoryInterface::class,
        static fn (Container $c): RefreshTokenRepositoryInterface => new RedisRefreshTokenRepository($c->get(Client::class)),
    );

    $container->singleton(ResponseFactory::class, static fn (): ResponseFactory => new ResponseFactory());
};
