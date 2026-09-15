<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

final class MigrationRunner
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function run(string $migrationsPath): void
    {
        $files = glob(rtrim($migrationsPath, '/\\') . '/*.sql') ?: [];
        sort($files);

        foreach ($files as $file) {
            $sql = trim((string) file_get_contents($file));

            if ($sql !== '') {
                $this->pdo->exec($sql);
            }
        }
    }
}
