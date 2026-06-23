<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RegularizationInstallmentStatus: string
{
    use HasOptions;

    case Scheduled = 'scheduled';
    case Paid = 'paid';
    case PartiallyPaid = 'partially_paid';
    case Overdue = 'overdue';
    case Waived = 'waived';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Agendada',
            self::Paid => 'Paga',
            self::PartiallyPaid => 'Parcialmente paga',
            self::Overdue => 'Em atraso',
            self::Waived => 'Dispensada',
            self::Cancelled => 'Cancelada',
        };
    }
}
