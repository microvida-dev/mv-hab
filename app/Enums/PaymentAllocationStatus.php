<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PaymentAllocationStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Reversed = 'reversed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativa',
            self::Reversed => 'Estornada',
            self::Cancelled => 'Cancelada',
        };
    }
}
