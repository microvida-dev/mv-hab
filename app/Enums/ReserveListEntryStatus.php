<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ReserveListEntryStatus: string
{
    use HasOptions;

    case Waiting = 'waiting';
    case Called = 'called';
    case Offered = 'offered';
    case Accepted = 'accepted';
    case Refused = 'refused';
    case Expired = 'expired';
    case Withdrawn = 'withdrawn';
    case Removed = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Em espera',
            self::Called => 'Chamado',
            self::Offered => 'Com oferta',
            self::Accepted => 'Aceite',
            self::Refused => 'Recusado',
            self::Expired => 'Expirado',
            self::Withdrawn => 'Desistido',
            self::Removed => 'Removido',
        };
    }
}
