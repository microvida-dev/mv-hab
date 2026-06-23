<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AllocationMethod: string
{
    use HasOptions;

    case Ranking = 'ranking';
    case Lottery = 'lottery';
    case RankingThenLottery = 'ranking_then_lottery';
    case PreferenceBased = 'preference_based';
    case ManualWithJustification = 'manual_with_justification';

    public function label(): string
    {
        return match ($this) {
            self::Ranking => 'Ranking',
            self::Lottery => 'Sorteio',
            self::RankingThenLottery => 'Ranking e sorteio',
            self::PreferenceBased => 'Preferências',
            self::ManualWithJustification => 'Manual com fundamentação',
        };
    }
}
