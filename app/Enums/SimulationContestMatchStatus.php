<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum SimulationContestMatchStatus: string
{
    use HasOptions;

    case Strong = 'strong';
    case Possible = 'possible';
    case RequiresReview = 'requires_review';
    case NotRecommended = 'not_recommended';

    public function label(): string
    {
        return match ($this) {
            self::Strong => 'Compatibilidade elevada',
            self::Possible => 'Compatibilidade possível',
            self::RequiresReview => 'Requer análise',
            self::NotRecommended => 'Não recomendado',
        };
    }
}
