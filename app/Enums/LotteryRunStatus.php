<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LotteryRunStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case ParticipantsLoaded = 'participants_loaded';
    case ParticipantsLocked = 'participants_locked';
    case Ready = 'ready';
    case Running = 'running';
    case Completed = 'completed';
    case Validated = 'validated';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Superseded = 'superseded';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::ParticipantsLoaded => 'Participantes carregados',
            self::ParticipantsLocked => 'Participantes bloqueados',
            self::Ready => 'Pronto',
            self::Running => 'Em execução',
            self::Completed => 'Concluído',
            self::Validated => 'Validado',
            self::Failed => 'Falhado',
            self::Cancelled => 'Cancelado',
            self::Superseded => 'Substituído',
            self::Locked => 'Bloqueado',
        };
    }
}
