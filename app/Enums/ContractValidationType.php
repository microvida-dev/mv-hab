<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContractValidationType: string
{
    use HasOptions;

    case Legal = 'legal';
    case Financial = 'financial';
    case Administrative = 'administrative';
    case Technical = 'technical';
    case Final = 'final';

    public function label(): string
    {
        return match ($this) {
            self::Legal => 'Jurídica',
            self::Financial => 'Financeira',
            self::Administrative => 'Administrativa',
            self::Technical => 'Técnica',
            self::Final => 'Final',
        };
    }
}
