<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EligibilityCriterionResult: string
{
    use HasOptions;

    case Passed = 'passed';
    case Failed = 'failed';
    case RequiresReview = 'requires_review';
    case InsufficientData = 'insufficient_data';
    case NotApplicable = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::Passed => 'Cumprido',
            self::Failed => 'Não cumprido',
            self::RequiresReview => 'Requer análise',
            self::InsufficientData => 'Dados insuficientes',
            self::NotApplicable => 'Não aplicável',
        };
    }
}
