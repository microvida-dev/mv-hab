<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TenantInvoiceStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Issued = 'issued';
    case Sent = 'sent';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
    case Voided = 'voided';
    case UnderReview = 'under_review';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Issued => 'Emitida',
            self::Sent => 'Enviada',
            self::PartiallyPaid => 'Parcialmente paga',
            self::Paid => 'Paga',
            self::Overdue => 'Em atraso',
            self::Cancelled => 'Cancelada',
            self::Voided => 'Anulada',
            self::UnderReview => 'Em revisão',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Paid, self::Cancelled, self::Voided], true);
    }
}
