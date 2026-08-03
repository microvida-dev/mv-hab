<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationResultExportMode: string
{
    use HasOptions;

    case CurrentState = 'current_state';
    case SealedBatch = 'sealed_batch';
    case PhaseSnapshot = 'phase_snapshot';
    case DeltaBetweenBatches = 'delta_between_batches';
    case DeltaSinceDatetime = 'delta_since_datetime';
    case FinalResult = 'final_result';

    public function label(): string
    {
        return match ($this) {
            self::CurrentState => 'Estado operacional atual',
            self::SealedBatch => 'Lote selado',
            self::PhaseSnapshot => 'Snapshot de fase publicado',
            self::DeltaBetweenBatches => 'Diferenças entre lotes',
            self::DeltaSinceDatetime => 'Alterações desde uma data',
            self::FinalResult => 'Último resultado oficial',
        };
    }

    public function isDelta(): bool
    {
        return in_array($this, [
            self::DeltaBetweenBatches,
            self::DeltaSinceDatetime,
        ], true);
    }
}
