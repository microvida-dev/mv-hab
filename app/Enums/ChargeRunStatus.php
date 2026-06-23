<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ChargeRunStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Running = 'running';
    case Completed = 'completed';
    case CompletedWithWarnings = 'completed_with_warnings';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Running => 'Em execução',
            self::Completed => 'Concluída',
            self::CompletedWithWarnings => 'Concluída com avisos',
            self::Failed => 'Falhada',
            self::Cancelled => 'Cancelada',
        };
    }
}
