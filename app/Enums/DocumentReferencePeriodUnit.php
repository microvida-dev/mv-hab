<?php

namespace App\Enums;

enum DocumentReferencePeriodUnit: string
{
    case Month = 'month';

    public function label(): string
    {
        return match ($this) {
            self::Month => 'Mensal',
        };
    }
}
