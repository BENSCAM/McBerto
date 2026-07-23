<?php

namespace App\Enums;

enum UserRole: string
{
    case Cashier = 'cashier';
    case Manager = 'manager';
    case Owner = 'owner';

    public function label(): string
    {
        return match ($this) {
            self::Cashier => 'Caissier',
            self::Manager => 'Gestionnaire',
            self::Owner => 'Propriétaire',
        };
    }
}
