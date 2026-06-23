<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TypologyAdequacyResult: string
{
    use HasOptions;

    case Adequate = 'adequate';
    case Inadequate = 'inadequate';
    case RequiresManualReview = 'requires_manual_review';
    case NotApplicable = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::Adequate => 'Adequada',
            self::Inadequate => 'Inadequada',
            self::RequiresManualReview => 'Requer análise manual',
            self::NotApplicable => 'Não aplicável',
        };
    }
}
