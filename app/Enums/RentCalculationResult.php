<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RentCalculationResult: string
{
    use HasOptions;

    case Applied = 'applied';
    case NotApplicable = 'not_applicable';
    case MissingData = 'missing_data';
    case RequiresManualReview = 'requires_manual_review';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Aplicada',
            self::NotApplicable => 'Não aplicável',
            self::MissingData => 'Dados em falta',
            self::RequiresManualReview => 'Requer revisão manual',
            self::Failed => 'Falhou',
        };
    }
}
