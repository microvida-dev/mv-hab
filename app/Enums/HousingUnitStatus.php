<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum HousingUnitStatus: string
{
    use HasOptions;

    case Available = 'available';
    case Occupied = 'occupied';
    case Maintenance = 'maintenance';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponível',
            self::Occupied => 'Ocupada',
            self::Maintenance => 'Em manutenção',
            self::Inactive => 'Inativa',
        };
    }
}
