<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AllocationStatus: string
{
    use HasOptions;

    case Proposed = 'proposed';
    case Offered = 'offered';
    case Accepted = 'accepted';
    case Refused = 'refused';
    case Expired = 'expired';
    case Withdrawn = 'withdrawn';
    case Cancelled = 'cancelled';
    case Superseded = 'superseded';
    case ReadyForContract = 'ready_for_contract';

    public function label(): string
    {
        return match ($this) {
            self::Proposed => 'Proposta',
            self::Offered => 'Oferecida',
            self::Accepted => 'Aceite',
            self::Refused => 'Recusada',
            self::Expired => 'Expirada',
            self::Withdrawn => 'Desistida',
            self::Cancelled => 'Cancelada',
            self::Superseded => 'Substituída',
            self::ReadyForContract => 'Pronta para contrato',
        };
    }
}
