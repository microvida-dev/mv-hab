<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InternalAlertStatus: string
{
    use HasOptions;

    case Open = 'open';
    case Seen = 'seen';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberto',
            self::Seen => 'Visto',
            self::InProgress => 'Em tratamento',
            self::Resolved => 'Resolvido',
            self::Dismissed => 'Dispensado',
            self::Expired => 'Expirado',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Open, self::Seen, self::InProgress], true);
    }
}
