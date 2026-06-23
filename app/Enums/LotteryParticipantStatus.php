<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LotteryParticipantStatus: string
{
    use HasOptions;

    case Included = 'included';
    case Excluded = 'excluded';
    case Withdrawn = 'withdrawn';
    case Notified = 'notified';
    case Present = 'present';
    case Absent = 'absent';
    case JustifiedAbsence = 'justified_absence';
    case Winner = 'winner';
    case Reserve = 'reserve';
    case Disqualified = 'disqualified';

    public function label(): string
    {
        return match ($this) {
            self::Included => 'Incluído',
            self::Excluded => 'Excluído',
            self::Withdrawn => 'Desistente',
            self::Notified => 'Notificado',
            self::Present => 'Presente',
            self::Absent => 'Ausente',
            self::JustifiedAbsence => 'Ausência justificada',
            self::Winner => 'Vencedor',
            self::Reserve => 'Suplente',
            self::Disqualified => 'Desclassificado',
        };
    }
}
