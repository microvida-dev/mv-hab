<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContestHousingUnitStatus: string
{
    use HasOptions;

    case Available = 'available';
    case Reserved = 'reserved';
    case Allocated = 'allocated';
    case Accepted = 'accepted';
    case Refused = 'refused';
    case Withdrawn = 'withdrawn';
    case Unavailable = 'unavailable';
    case Removed = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponível',
            self::Reserved => 'Reservada',
            self::Allocated => 'Atribuída',
            self::Accepted => 'Aceite',
            self::Refused => 'Recusada',
            self::Withdrawn => 'Desistida',
            self::Unavailable => 'Indisponível',
            self::Removed => 'Removida',
        };
    }
}
