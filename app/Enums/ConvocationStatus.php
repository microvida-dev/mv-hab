<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ConvocationStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Generated = 'generated';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Generated => 'Gerada',
            self::Sent => 'Enviada',
            self::Delivered => 'Entregue',
            self::Read => 'Lida',
            self::Failed => 'Falhada',
            self::Cancelled => 'Cancelada',
            self::Expired => 'Expirada',
        };
    }
}
