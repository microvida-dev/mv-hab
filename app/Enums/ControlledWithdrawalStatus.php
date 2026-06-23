<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ControlledWithdrawalStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case PendingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::PendingConfirmation => 'A aguardar confirmação',
            self::Confirmed => 'Confirmada',
            self::Rejected => 'Rejeitada',
            self::Cancelled => 'Cancelada',
            self::Completed => 'Concluída',
        };
    }
}
