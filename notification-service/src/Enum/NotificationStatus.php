<?php

declare(strict_types=1);

namespace App\Enum;

enum NotificationStatus: string
{
    case Pending = 'pending';
    case Retrying = 'retrying';
    case Sent = 'sent';
    case Failed = 'failed';
}
