<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ListChangeType: string
{
    use HasOptions;

    case RankChanged = 'rank_changed';
    case ScoreChanged = 'score_changed';
    case StatusChanged = 'status_changed';
    case ExclusionRemoved = 'exclusion_removed';
    case ExclusionAdded = 'exclusion_added';
    case EntryAdded = 'entry_added';
    case EntryRemoved = 'entry_removed';
    case ComplaintEffect = 'complaint_effect';
    case HearingEffect = 'hearing_effect';
    case ManualCorrection = 'manual_correction';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::RankChanged => 'Alteração de posição',
            self::ScoreChanged => 'Alteração de pontuação',
            self::StatusChanged => 'Alteração de estado',
            self::ExclusionRemoved => 'Exclusão removida',
            self::ExclusionAdded => 'Exclusão adicionada',
            self::EntryAdded => 'Entrada adicionada',
            self::EntryRemoved => 'Entrada removida',
            self::ComplaintEffect => 'Efeito de reclamação',
            self::HearingEffect => 'Efeito de audiência',
            self::ManualCorrection => 'Correção manual',
            self::Other => 'Outro',
        };
    }
}
