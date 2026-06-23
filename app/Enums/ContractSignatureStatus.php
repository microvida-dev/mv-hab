<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContractSignatureStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Signed = 'signed';
    case Refused = 'refused';
    case Waived = 'waived';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Signed => 'Assinada',
            self::Refused => 'Recusada',
            self::Waived => 'Dispensada',
            self::Cancelled => 'Cancelada',
        };
    }
}
