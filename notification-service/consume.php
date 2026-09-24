<?php

declare(strict_types=1);

use App\Config\EnvLoader;
use App\Container\Container;
use App\Messaging\EventConsumer;

require __DIR__ . '/vendor/autoload.php';

$basePath = __DIR__;

EnvLoader::load($basePath . '/.env');

$container = new Container();
(require $basePath . '/config/services.php')($container);

echo "Notification consumer started, waiting for events...\n";

$container->get(EventConsumer::class)->run();
