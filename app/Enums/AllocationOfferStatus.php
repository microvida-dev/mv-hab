<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AllocationOfferStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Issued = 'issued';
    case PendingResponse = 'pending_response';
    case Accepted = 'accepted';
    case Refused = 'refused';
    case Expired = 'expired';
    case Withdrawn = 'withdrawn';
    case Cancelled = 'cancelled';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Issued => 'Emitida',
            self::PendingResponse => 'A aguardar resposta',
            self::Accepted => 'Aceite',
            self::Refused => 'Recusada',
            self::Expired => 'Expirada',
            self::Withdrawn => 'Desistida',
            self::Cancelled => 'Cancelada',
            self::Superseded => 'Substituída',
        };
    }
}
