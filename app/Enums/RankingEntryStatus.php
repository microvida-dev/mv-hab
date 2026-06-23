<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RankingEntryStatus: string
{
    use HasOptions;

    case Ranked = 'ranked';
    case Tied = 'tied';
    case Excluded = 'excluded';
    case RequiresManualReview = 'requires_manual_review';

    public function label(): string
    {
        return match ($this) {
            self::Ranked => 'Classificada',
            self::Tied => 'Empatada',
            self::Excluded => 'Excluída',
            self::RequiresManualReview => 'Requer avaliação manual',
        };
    }
}
