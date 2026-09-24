<?php

declare(strict_types=1);

use App\Config\Config;
use App\Config\EnvLoader;
use App\Container\Container;
use App\Dto\CreateUserDto;
use App\Enum\UserRole;
use App\Repository\UserRepositoryInterface;
use App\Service\PasswordService;

require __DIR__ . '/vendor/autoload.php';

$basePath = __DIR__;

EnvLoader::load($basePath . '/.env');

$container = new Container();
(require $basePath . '/config/services.php')($container);

try {
    $config = $container->get(Config::class);
    $email = strtolower(trim($config->require('ADMIN_EMAIL')));
    $password = $config->require('ADMIN_PASSWORD');
    $name = $config->get('ADMIN_NAME', 'Administrator');

    $users = $container->get(UserRepositoryInterface::class);
    $existing = $users->findByEmail($email);

    if ($existing !== null) {
        if ($existing->role !== UserRole::Admin) {
            $users->setRole($existing->id, UserRole::Admin);
            echo "Existing user [{$email}] promoted to ADMIN\n";
        } else {
            echo "Admin [{$email}] already exists, nothing to do\n";
        }

        exit(0);
    }

    $passwordService = $container->get(PasswordService::class);
    $admin = $users->create(new CreateUserDto(
        $name,
        $email,
        $passwordService->hash($password),
        bin2hex(random_bytes(32)),
        null,
        UserRole::Admin,
    ));

    // Сидовый админ создаётся доверенно — письмо ему не отправлялось, гейт по email не нужен
    $users->markEmailVerified($admin->id);

    echo "Admin [{$admin->email}] created with id {$admin->id}\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}
