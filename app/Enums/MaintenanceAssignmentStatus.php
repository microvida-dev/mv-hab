<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MaintenanceAssignmentStatus: string
{
    use HasOptions;

    case Assigned = 'assigned';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Atribuído',
            self::Accepted => 'Aceite',
            self::Rejected => 'Recusado',
            self::Completed => 'Concluído',
            self::Cancelled => 'Cancelado',
        };
    }
}
