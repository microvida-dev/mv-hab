<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RentReviewStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Requested = 'requested';
    case UnderReview = 'under_review';
    case RequiresDocuments = 'requires_documents';
    case Calculated = 'calculated';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Applied = 'applied';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Requested => 'Solicitada',
            self::UnderReview => 'Em análise',
            self::RequiresDocuments => 'Requer documentos',
            self::Calculated => 'Calculada',
            self::Approved => 'Aprovada',
            self::Rejected => 'Rejeitada',
            self::Applied => 'Aplicada',
            self::Cancelled => 'Cancelada',
        };
    }
}
