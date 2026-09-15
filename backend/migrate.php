<?php

declare(strict_types=1);

use App\Config\EnvLoader;
use App\Container\Container;
use App\Database\MigrationRunner;

require __DIR__ . '/vendor/autoload.php';

$basePath = __DIR__;

EnvLoader::load($basePath . '/.env');

$container = new Container();
(require $basePath . '/config/services.php')($container);

try {
    $container->get(MigrationRunner::class)->run($basePath . '/database/migrations');
    echo "Migrated\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Err: ' . $e->getMessage() . "\n");
    exit(1);
}
