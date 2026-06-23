<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RentInstallmentStatus: string
{
    use HasOptions;

    case Scheduled = 'scheduled';
    case Issued = 'issued';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Waived = 'waived';
    case Cancelled = 'cancelled';
    case UnderAgreement = 'under_agreement';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Agendada',
            self::Issued => 'Emitida',
            self::PartiallyPaid => 'Parcialmente paga',
            self::Paid => 'Paga',
            self::Overdue => 'Em atraso',
            self::Waived => 'Dispensada',
            self::Cancelled => 'Cancelada',
            self::UnderAgreement => 'Em acordo',
        };
    }
}
