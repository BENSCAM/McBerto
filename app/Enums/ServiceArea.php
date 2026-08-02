<?php

namespace App\Enums;

enum ServiceArea: string
{
    case Standard = 'standard';
    case Vip = 'vip';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::Vip => 'VIP',
        };
    }
}
