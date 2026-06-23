<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TenantTransitionStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Ready = 'ready';
    case Completed = 'completed';
    case Blocked = 'blocked';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Ready => 'Pronta',
            self::Completed => 'Concluída',
            self::Blocked => 'Bloqueada',
            self::Failed => 'Falhada',
            self::Cancelled => 'Cancelada',
        };
    }
}
