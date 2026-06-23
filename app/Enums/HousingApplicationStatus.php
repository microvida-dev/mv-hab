<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum HousingApplicationStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Submitted => 'Submetida',
            self::UnderReview => 'Em análise',
            self::Approved => 'Aprovada',
            self::Rejected => 'Rejeitada',
            self::Cancelled => 'Cancelada',
        };
    }
}
