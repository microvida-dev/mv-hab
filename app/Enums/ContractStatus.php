<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContractStatus: string
{
    use HasOptions;

    case Preparation = 'preparation';
    case Issued = 'issued';
    case Signed = 'signed';
    case Active = 'active';
    case Suspended = 'suspended';
    case Terminated = 'terminated';
    case Renewed = 'renewed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Preparation => 'Em preparação',
            self::Issued => 'Emitido',
            self::Signed => 'Assinado',
            self::Active => 'Ativo',
            self::Suspended => 'Suspenso',
            self::Terminated, self::Ended => 'Terminado',
            self::Renewed => 'Renovado',
            self::Cancelled => 'Cancelado',
            self::Expired => 'Expirado',
        };
    }
}
