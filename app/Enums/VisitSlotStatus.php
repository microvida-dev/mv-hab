<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum VisitSlotStatus: string
{
    use HasOptions;

    case Available = 'available';
    case Reserved = 'reserved';
    case Full = 'full';
    case Blocked = 'blocked';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponível',
            self::Reserved => 'Reservado',
            self::Full => 'Completo',
            self::Blocked => 'Bloqueado',
            self::Cancelled => 'Cancelado',
            self::Expired => 'Expirado',
        };
    }
}
