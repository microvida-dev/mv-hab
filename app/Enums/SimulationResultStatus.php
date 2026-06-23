<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum SimulationResultStatus: string
{
    use HasOptions;

    case LikelyEligible = 'likely_eligible';
    case LikelyIneligible = 'likely_ineligible';
    case RequiresReview = 'requires_review';
    case InsufficientData = 'insufficient_data';
    case NoMatchingContest = 'no_matching_contest';

    public function label(): string
    {
        return match ($this) {
            self::LikelyEligible => 'Potencialmente elegível',
            self::LikelyIneligible => 'Potencialmente não elegível',
            self::RequiresReview => 'Requer análise',
            self::InsufficientData => 'Dados insuficientes',
            self::NoMatchingContest => 'Sem concurso compatível',
        };
    }
}
