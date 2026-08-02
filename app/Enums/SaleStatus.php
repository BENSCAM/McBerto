<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Validée',
            self::Canceled => 'Annulée',
        };
    }
}
