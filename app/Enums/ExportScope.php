<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ExportScope: string
{
    use HasOptions;

    case Aggregated = 'aggregated';
    case Pseudonymized = 'pseudonymized';
    case Nominal = 'nominal';
    case Full = 'full';

    public function label(): string
    {
        return match ($this) {
            self::Aggregated => 'Agregado',
            self::Pseudonymized => 'Pseudonimizado',
            self::Nominal => 'Nominal',
            self::Full => 'Completo',
        };
    }

    public function containsPersonalData(): bool
    {
        return in_array($this, [self::Nominal, self::Full], true);
    }
}
