<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TicketStatus: string
{
    use HasOptions;

    case Open = 'open';
    case PendingCandidate = 'pending_candidate';
    case PendingStaff = 'pending_staff';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
    case Reopened = 'reopened';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberto',
            self::PendingCandidate => 'Pendente do candidato',
            self::PendingStaff => 'Pendente dos serviços',
            self::InProgress => 'Em tratamento',
            self::Resolved => 'Resolvido',
            self::Closed => 'Fechado',
            self::Cancelled => 'Cancelado',
            self::Reopened => 'Reaberto',
        };
    }

    public function acceptsCandidateReply(): bool
    {
        return ! in_array($this, [self::Resolved, self::Closed, self::Cancelled], true);
    }
}
