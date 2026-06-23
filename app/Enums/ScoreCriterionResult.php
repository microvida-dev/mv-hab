<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ScoreCriterionResult: string
{
    use HasOptions;

    case Applied = 'applied';
    case NotApplicable = 'not_applicable';
    case RequiresManualReview = 'requires_manual_review';
    case MissingData = 'missing_data';
    case Failed = 'failed';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Aplicado',
            self::NotApplicable => 'Não aplicável',
            self::RequiresManualReview => 'Requer avaliação manual',
            self::MissingData => 'Dados em falta',
            self::Failed => 'Falhado',
            self::Manual => 'Manual',
        };
    }
}
