<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CorrectionRevalidationAggregateResult: string
{
    use HasOptions;

    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case RequiresManualDecision = 'requires_manual_decision';

    public function label(): string
    {
        return match ($this) {
            self::Accepted => 'Aperfeiçoamento aceite',
            self::Rejected => 'Aperfeiçoamento não aceite',
            self::RequiresManualDecision => 'Requer decisão manual',
        };
    }
}
