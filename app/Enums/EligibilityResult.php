<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EligibilityResult: string
{
    use HasOptions;

    case Eligible = 'eligible';
    case Ineligible = 'ineligible';
    case RequiresReview = 'requires_review';
    case InsufficientData = 'insufficient_data';
    case NotApplicable = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::Eligible => 'Elegível',
            self::Ineligible => 'Não elegível',
            self::RequiresReview => 'Requer análise',
            self::InsufficientData => 'Dados insuficientes',
            self::NotApplicable => 'Não aplicável',
        };
    }
}
