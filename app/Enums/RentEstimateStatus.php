<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RentEstimateStatus: string
{
    use HasOptions;

    case Estimated = 'estimated';
    case RequiresReview = 'requires_review';
    case InsufficientIncomeData = 'insufficient_income_data';
    case NoRuleAvailable = 'no_rule_available';
    case NotApplicable = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::Estimated => 'Estimativa calculada',
            self::RequiresReview => 'Requer análise',
            self::InsufficientIncomeData => 'Rendimento insuficiente',
            self::NoRuleAvailable => 'Sem regra configurada',
            self::NotApplicable => 'Não aplicável',
        };
    }
}
