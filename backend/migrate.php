<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Database\Database;

try {
    Database::migrate();
    echo "Migrated\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Err: ' . $e->getMessage() . "\n");
    exit(1);
}
