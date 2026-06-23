<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ImpedimentSeverity: string
{
    use HasOptions;

    case Info = 'info';
    case Warning = 'warning';
    case Blocking = 'blocking';
    case RequiresReview = 'requires_review';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Informativo',
            self::Warning => 'Aviso',
            self::Blocking => 'Bloqueante',
            self::RequiresReview => 'Requer análise',
        };
    }
}
