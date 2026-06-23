<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InspectionStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Validated = 'validated';
    case Rejected = 'rejected';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Scheduled => 'Agendada',
            self::InProgress => 'Em execução',
            self::Completed => 'Concluída',
            self::Validated => 'Validada',
            self::Rejected => 'Rejeitada',
            self::Closed => 'Fechada',
            self::Cancelled => 'Cancelada',
        };
    }
}
