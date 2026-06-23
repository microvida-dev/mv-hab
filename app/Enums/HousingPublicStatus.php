<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum HousingPublicStatus: string
{
    use HasOptions;

    case Available = 'available';
    case Reserved = 'reserved';
    case Allocated = 'allocated';
    case UnderMaintenance = 'under_maintenance';
    case Unavailable = 'unavailable';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponível',
            self::Reserved => 'Reservada',
            self::Allocated => 'Atribuída',
            self::UnderMaintenance => 'Em manutenção',
            self::Unavailable => 'Indisponível',
            self::Closed => 'Encerrada',
        };
    }
}
