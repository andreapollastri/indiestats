<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Base = 'base';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Base => 'Base',
        };
    }
}
