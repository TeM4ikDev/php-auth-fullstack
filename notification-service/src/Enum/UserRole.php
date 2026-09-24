<?php

declare(strict_types=1);

namespace App\Enum;

enum UserRole: string
{
    case Customer = 'CUSTOMER';
    case Analyst = 'ANALYST';
    case Admin = 'ADMIN';

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function canManageUsers(): bool
    {
        return $this === self::Admin;
    }
}
