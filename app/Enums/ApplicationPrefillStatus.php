<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationPrefillStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case PendingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case Applied = 'applied';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::PendingConfirmation => 'Aguarda confirmação',
            self::Confirmed => 'Confirmado',
            self::Applied => 'Aplicado',
            self::Cancelled => 'Cancelado',
            self::Expired => 'Expirado',
        };
    }
}
