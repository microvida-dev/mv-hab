<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum IncomeChangeType: string
{
    use HasOptions;

    case IncomeChange = 'income_change';
    case HouseholdComposition = 'household_composition';
    case Unemployment = 'unemployment';
    case Retirement = 'retirement';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::IncomeChange => 'Alteração de rendimento',
            self::HouseholdComposition => 'Alteração do agregado',
            self::Unemployment => 'Desemprego',
            self::Retirement => 'Reforma',
            self::Other => 'Outro',
        };
    }
}
