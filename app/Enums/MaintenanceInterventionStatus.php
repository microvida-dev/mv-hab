<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MaintenanceInterventionStatus: string
{
    use HasOptions;

    case Planned = 'planned';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planeada',
            self::Scheduled => 'Agendada',
            self::InProgress => 'Em execução',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
            self::Failed => 'Falhada',
        };
    }
}
