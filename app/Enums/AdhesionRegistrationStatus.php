<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AdhesionRegistrationStatus: string
{
    use HasOptions;

    case Incomplete = 'incomplete';
    case Registered = 'registered';
    case Cancelled = 'cancelled';
    case Removed = 'removed';
    case Blocked = 'blocked';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Incomplete => 'Incompleto',
            self::Registered => 'Finalizado',
            self::Cancelled => 'Cancelado',
            self::Removed => 'Removido',
            self::Blocked => 'Bloqueado',
            self::Expired => 'Expirado',
        };
    }
}
