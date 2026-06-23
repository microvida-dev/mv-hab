<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum HousingLocationPrecision: string
{
    use HasOptions;

    case Exact = 'exact';
    case Street = 'street';
    case Parish = 'parish';
    case Approximate = 'approximate';

    public function label(): string
    {
        return match ($this) {
            self::Exact => 'Morada exata',
            self::Street => 'Rua sem número',
            self::Parish => 'Freguesia',
            self::Approximate => 'Localização aproximada',
        };
    }
}
