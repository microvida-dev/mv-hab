<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RegulatoryConfigurationStatus: string
{
    use HasOptions;

    case Complete = 'complete';
    case Incomplete = 'incomplete';
    case RequiresManualReview = 'requires_manual_review';

    public function label(): string
    {
        return match ($this) {
            self::Complete => 'Completa',
            self::Incomplete => 'Incompleta',
            self::RequiresManualReview => 'Requer revisão manual',
        };
    }
}
