<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DataReuseStatus: string
{
    use HasOptions;

    case Available = 'available';
    case RequiresConfirmation = 'requires_confirmation';
    case Confirmed = 'confirmed';
    case Applied = 'applied';
    case Outdated = 'outdated';
    case Expired = 'expired';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponível',
            self::RequiresConfirmation => 'Requer confirmação',
            self::Confirmed => 'Confirmada',
            self::Applied => 'Aplicada',
            self::Outdated => 'Desatualizada',
            self::Expired => 'Expirada',
            self::Blocked => 'Bloqueada',
        };
    }
}
