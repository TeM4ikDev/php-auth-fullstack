<?php

declare(strict_types=1);

namespace App\Database;

use App\Config\DatabaseConfig;
use PDO;
use PDOException;
use RuntimeException;

final class ConnectionFactory
{
    public function __construct(private readonly DatabaseConfig $config)
    {
    }

    public function create(): PDO
    {
        try {
            return new PDO($this->config->dsn, $this->config->username, $this->config->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException(
                sprintf('Could not connect to the database (%s): %s', $this->config->dsn, $e->getMessage()),
                0,
                $e,
            );
        }
    }
}
