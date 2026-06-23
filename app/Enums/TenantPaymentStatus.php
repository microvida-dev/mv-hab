<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TenantPaymentStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Registered = 'registered';
    case Confirmed = 'confirmed';
    case Reconciled = 'reconciled';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case Partial = 'partial';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Registered => 'Registado',
            self::Confirmed => 'Confirmado',
            self::Reconciled => 'Reconciliado',
            self::Failed => 'Falhado',
            self::Cancelled => 'Cancelado',
            self::Refunded => 'Reembolsado',
            self::Partial => 'Parcial',
        };
    }
}
