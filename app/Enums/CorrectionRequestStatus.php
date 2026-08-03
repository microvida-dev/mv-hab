<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CorrectionRequestStatus: string
{
    use HasOptions;

    case Notified = 'notified';
    case Open = 'open';
    case PartiallyCompleted = 'partially_completed';
    case Submitted = 'submitted';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Notified => 'Notificado',
            self::Open => 'Aberto',
            self::PartiallyCompleted => 'Parcialmente concluído',
            self::Submitted => 'Submetido',
            self::Expired => 'Expirado',
            self::Cancelled => 'Cancelado',
            self::Resolved => 'Resolvido',
        };
    }

    public function acceptsCandidateWork(): bool
    {
        return in_array($this, [
            self::Notified,
            self::Open,
            self::PartiallyCompleted,
        ], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Expired,
            self::Cancelled,
            self::Resolved,
        ], true);
    }
}
