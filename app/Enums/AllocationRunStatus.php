<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AllocationRunStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Ready = 'ready';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Ready => 'Pronta',
            self::Running => 'Em execução',
            self::Completed => 'Concluída',
            self::Failed => 'Falhada',
            self::Cancelled => 'Cancelada',
            self::Locked => 'Bloqueada',
        };
    }
}
