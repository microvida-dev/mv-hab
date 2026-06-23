<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum HearingType: string
{
    use HasOptions;

    case IntentionToExclude = 'intention_to_exclude';
    case IntentionToChangeRanking = 'intention_to_change_ranking';
    case IntentionToRejectComplaint = 'intention_to_reject_complaint';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::IntentionToExclude => 'Intenção de exclusão',
            self::IntentionToChangeRanking => 'Intenção de alteração de classificação',
            self::IntentionToRejectComplaint => 'Intenção de indeferimento de reclamação',
            self::Other => 'Outro',
        };
    }
}
