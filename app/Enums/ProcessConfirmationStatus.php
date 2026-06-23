<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ProcessConfirmationStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Generated = 'generated';
    case Sent = 'sent';
    case Read = 'read';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Generated => 'Gerada',
            self::Sent => 'Enviada',
            self::Read => 'Lida',
            self::Failed => 'Falhou',
            self::Cancelled => 'Cancelada',
        };
    }
}
