<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum OfficialNotificationStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Queued = 'queued';
    case Published = 'published';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Acknowledged = 'acknowledged';
    case Archived = 'archived';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Queued => 'Registada',
            self::Published => 'Publicada',
            self::Sent => 'Enviada',
            self::Delivered => 'Entregue',
            self::Read => 'Lida',
            self::Acknowledged => 'Tomada de conhecimento',
            self::Archived => 'Arquivada',
            self::Failed => 'Falhada',
            self::Cancelled => 'Cancelada',
            self::Expired => 'Expirada',
        };
    }
}
