<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RentCalculationMethod: string
{
    use HasOptions;

    case FixedPercentageOfIncome = 'fixed_percentage_of_income';
    case EffortRate = 'effort_rate';
    case IncomeBracket = 'income_bracket';
    case FixedAmount = 'fixed_amount';
    case Manual = 'manual';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::FixedPercentageOfIncome => 'Percentagem fixa do rendimento',
            self::EffortRate => 'Taxa de esforço',
            self::IncomeBracket => 'Escalão de rendimento',
            self::FixedAmount => 'Valor fixo',
            self::Manual => 'Manual',
            self::Custom => 'Personalizado',
        };
    }
}
