<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum KeyHandoverStatus: string
{
    use HasOptions;

    case PendingSchedule = 'pending_schedule';
    case Scheduled = 'scheduled';
    case Rescheduled = 'rescheduled';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case Missed = 'missed';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::PendingSchedule => 'Por agendar',
            self::Scheduled => 'Agendada',
            self::Rescheduled => 'Reagendada',
            self::Cancelled => 'Cancelada',
            self::Completed => 'Concluída',
            self::Missed => 'Falta',
            self::Blocked => 'Bloqueada',
        };
    }
}
