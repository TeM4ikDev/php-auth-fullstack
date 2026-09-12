<?php

declare(strict_types=1);

namespace App\Database;

use App\Config\Config;
use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static $connection;

    public static function connect(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = Config::database();

        try {
            self::$connection = new PDO($config['dsn'], $config['user'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException(
                sprintf('db not connected (%s): %s', $config['dsn'], $e->getMessage()),
                503,
                $e
            );
        }

        return self::$connection;
    }

    public static function migrate(): void
    {
        self::connect()->exec(Sql::CREATE_USERS_TABLE);
    }
}
