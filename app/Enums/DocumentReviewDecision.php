<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentReviewDecision: string
{
    use HasOptions;

    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Validated = 'validated';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submetido',
            self::UnderReview => 'Em análise',
            self::Validated => 'Validado',
            self::Rejected => 'Rejeitado',
            self::Expired => 'Expirado',
            self::Cancelled => 'Cancelado',
        };
    }
}
