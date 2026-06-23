<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ArrearStatus: string
{
    use HasOptions;

    case Open = 'open';
    case Notified = 'notified';
    case UnderAgreement = 'under_agreement';
    case PartiallyRegularized = 'partially_regularized';
    case Regularized = 'regularized';
    case Waived = 'waived';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberto',
            self::Notified => 'Notificado',
            self::UnderAgreement => 'Em acordo',
            self::PartiallyRegularized => 'Parcialmente regularizado',
            self::Regularized => 'Regularizado',
            self::Waived => 'Dispensado',
            self::Closed => 'Fechado',
            self::Cancelled => 'Cancelado',
        };
    }
}
