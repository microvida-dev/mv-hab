<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ScoringRunStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Running => 'Em execução',
            self::Completed => 'Concluída',
            self::Failed => 'Falhada',
            self::Cancelled => 'Cancelada',
            self::Locked => 'Bloqueada',
        };
    }
}
