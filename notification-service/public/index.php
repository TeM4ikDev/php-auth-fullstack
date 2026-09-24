<?php

declare(strict_types=1);

use App\Kernel;

require dirname(__DIR__) . '/vendor/autoload.php';

Kernel::boot(dirname(__DIR__))->run();
