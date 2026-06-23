<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CorrectionRequestStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Issued = 'issued';
    case Open = 'open';
    case PartiallyResponded = 'partially_responded';
    case Responded = 'responded';
    case Overdue = 'overdue';
    case UnderReview = 'under_review';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Issued => 'Emitido',
            self::Open => 'Aberto',
            self::PartiallyResponded => 'Parcialmente respondido',
            self::Responded => 'Respondido',
            self::Overdue => 'Vencido',
            self::UnderReview => 'Em análise',
            self::Accepted => 'Aceite',
            self::Rejected => 'Rejeitado',
            self::Closed => 'Fechado',
            self::Cancelled => 'Cancelado',
        };
    }
}
