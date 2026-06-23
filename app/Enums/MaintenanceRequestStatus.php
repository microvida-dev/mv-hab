<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MaintenanceRequestStatus: string
{
    use HasOptions;

    case New = 'new';
    case UnderReview = 'under_review';
    case Open = 'open';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Rejected = 'rejected';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Novo',
            self::UnderReview => 'Em análise',
            self::Open => 'Aberto',
            self::Scheduled => 'Agendado',
            self::InProgress => 'Em progresso',
            self::Resolved => 'Resolvido',
            self::Rejected => 'Rejeitado',
            self::Closed => 'Fechado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Rejected, self::Closed, self::Cancelled], true);
    }
}
