<?php

declare(strict_types=1);

use App\Config\AppConfig;
use App\Config\Config;
use App\Config\JwtConfig;
use App\Config\MailConfig;
use App\Config\MongoConfig;
use App\Config\MongoConfigFactory;
use App\Config\RabbitMqConfig;
use App\Container\Container;
use App\Http\ResponseFactory;
use App\Repository\MongoNotificationRepository;
use App\Repository\NotificationRepositoryInterface;
use App\Service\JwtService;
use App\Service\MailerInterface;
use App\Service\PhpMailerService;

return static function (Container $container): void {
    $container->singleton(Config::class, static fn (): Config => new Config());

    $container->singleton(
        AppConfig::class,
        static fn (Container $c): AppConfig => AppConfig::fromConfig($c->get(Config::class)),
    );

    $container->singleton(
        JwtConfig::class,
        static fn (Container $c): JwtConfig => JwtConfig::fromConfig($c->get(Config::class)),
    );

    $container->singleton(JwtService::class, static fn (Container $c): JwtService => new JwtService($c->get(JwtConfig::class)));

    $container->singleton(
        MongoConfig::class,
        static fn (Container $c): MongoConfig => $c->get(MongoConfigFactory::class)->create(),
    );

    $container->singleton(
        MailConfig::class,
        static fn (Container $c): MailConfig => MailConfig::fromConfig($c->get(Config::class)),
    );

    $container->singleton(
        RabbitMqConfig::class,
        static fn (Container $c): RabbitMqConfig => RabbitMqConfig::fromConfig($c->get(Config::class)),
    );

    $container->singleton(
        NotificationRepositoryInterface::class,
        static fn (Container $c): NotificationRepositoryInterface => new MongoNotificationRepository($c->get(MongoConfig::class)),
    );

    $container->singleton(
        MailerInterface::class,
        static fn (Container $c): MailerInterface => new PhpMailerService($c->get(MailConfig::class)),
    );

    $container->singleton(ResponseFactory::class, static fn (): ResponseFactory => new ResponseFactory());
};
