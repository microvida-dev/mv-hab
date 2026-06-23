<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RentReviewType: string
{
    use HasOptions;

    case Annual = 'annual';
    case IncomeChange = 'income_change';
    case Administrative = 'administrative';
    case Correction = 'correction';

    public function label(): string
    {
        return match ($this) {
            self::Annual => 'Anual',
            self::IncomeChange => 'Alteração de rendimentos',
            self::Administrative => 'Administrativa',
            self::Correction => 'Correção',
        };
    }
}
