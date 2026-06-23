<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum VisitStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Requested = 'requested';
    case PendingConfirmation = 'pending_confirmation';
    case Confirmed = 'confirmed';
    case Rescheduled = 'rescheduled';
    case CancelledByCandidate = 'cancelled_by_candidate';
    case CancelledByStaff = 'cancelled_by_staff';
    case Completed = 'completed';
    case Missed = 'missed';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Requested => 'Solicitada',
            self::PendingConfirmation => 'Pendente de confirmação',
            self::Confirmed => 'Confirmada',
            self::Rescheduled => 'Reagendada',
            self::CancelledByCandidate => 'Cancelada pelo candidato',
            self::CancelledByStaff => 'Cancelada pelos serviços',
            self::Completed => 'Concluída',
            self::Missed => 'Não comparência',
            self::Rejected => 'Recusada',
            self::Expired => 'Expirada',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Requested, self::PendingConfirmation, self::Confirmed, self::Rescheduled], true);
    }
}
