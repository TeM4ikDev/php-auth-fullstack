<?php

declare(strict_types=1);

namespace App\Enum;

enum TokenType: string
{
    case Access = 'access';
    case Refresh = 'refresh';
}
