<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InspectionType: string
{
    use HasOptions;

    case Initial = 'initial';
    case Periodic = 'periodic';
    case Final = 'final';
    case Extraordinary = 'extraordinary';

    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Inicial',
            self::Periodic => 'Periódica',
            self::Final => 'Final',
            self::Extraordinary => 'Extraordinária',
        };
    }
}
