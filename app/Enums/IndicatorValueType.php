<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum IndicatorValueType: string
{
    use HasOptions;

    case Count = 'count';
    case Percentage = 'percentage';
    case Currency = 'currency';
    case Days = 'days';
    case Ratio = 'ratio';
    case Average = 'average';
    case Sum = 'sum';

    public function label(): string
    {
        return match ($this) {
            self::Count => 'Contagem',
            self::Percentage => 'Percentagem',
            self::Currency => 'Moeda',
            self::Days => 'Dias',
            self::Ratio => 'Rácio',
            self::Average => 'Média',
            self::Sum => 'Soma',
        };
    }
}
