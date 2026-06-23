<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AnnualDocumentUpdateStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Requested = 'requested';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Requested => 'Solicitado',
            self::Submitted => 'Submetido',
            self::UnderReview => 'Em análise',
            self::Accepted => 'Aceite',
            self::Rejected => 'Rejeitado',
            self::Overdue => 'Em atraso',
            self::Cancelled => 'Cancelado',
            self::Closed => 'Fechado',
        };
    }
}
