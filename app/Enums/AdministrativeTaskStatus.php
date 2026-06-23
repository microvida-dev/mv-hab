<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AdministrativeTaskStatus: string
{
    use HasOptions;

    case Open = 'open';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberta',
            self::InProgress => 'Em curso',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
            self::Overdue => 'Vencida',
        };
    }
}
