<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RentCalculationStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Calculated = 'calculated';
    case RequiresManualReview = 'requires_manual_review';
    case ManuallyReviewed = 'manually_reviewed';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Superseded = 'superseded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Calculated => 'Calculado',
            self::RequiresManualReview => 'Requer revisão manual',
            self::ManuallyReviewed => 'Revisto manualmente',
            self::Approved => 'Aprovado',
            self::Rejected => 'Rejeitado',
            self::Superseded => 'Substituído',
            self::Cancelled => 'Cancelado',
        };
    }
}
