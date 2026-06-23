<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DepositStatus: string
{
    use HasOptions;

    case NotRequired = 'not_required';
    case Pending = 'pending';
    case Requested = 'requested';
    case Paid = 'paid';
    case Waived = 'waived';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case Retained = 'retained';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'Não exigida',
            self::Pending => 'Pendente',
            self::Requested => 'Solicitada',
            self::Paid => 'Paga',
            self::Waived => 'Dispensada',
            self::PartiallyRefunded => 'Parcialmente devolvida',
            self::Refunded => 'Devolvida',
            self::Retained => 'Retida',
            self::Cancelled => 'Cancelada',
        };
    }
}
