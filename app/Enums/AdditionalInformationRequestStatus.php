<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AdditionalInformationRequestStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Issued = 'issued';
    case Open = 'open';
    case Responded = 'responded';
    case Overdue = 'overdue';
    case UnderReview = 'under_review';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Issued => 'Emitido',
            self::Open => 'Aberto',
            self::Responded => 'Respondido',
            self::Overdue => 'Vencido',
            self::UnderReview => 'Em análise',
            self::Closed => 'Fechado',
            self::Cancelled => 'Cancelado',
        };
    }
}
