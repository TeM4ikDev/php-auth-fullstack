<?php

declare(strict_types=1);

use App\Config\AppConfig;
use App\Config\Config;

return static fn (Config $config): AppConfig => AppConfig::fromConfig($config);
