<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationReviewResult: string
{
    use HasOptions;

    case Passed = 'passed';
    case Failed = 'failed';
    case RequiresCorrection = 'requires_correction';
    case RequiresManualReview = 'requires_manual_review';
    case InsufficientData = 'insufficient_data';
    case NotApplicable = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::Passed => 'Conforme',
            self::Failed => 'Não conforme',
            self::RequiresCorrection => 'Requer aperfeiçoamento',
            self::RequiresManualReview => 'Requer análise manual',
            self::InsufficientData => 'Dados insuficientes',
            self::NotApplicable => 'Não aplicável',
        };
    }
}
