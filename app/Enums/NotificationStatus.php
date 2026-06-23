<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum NotificationStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Queued = 'queued';
    case Published = 'published';
    case Read = 'read';
    case Acknowledged = 'acknowledged';
    case Archived = 'archived';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Queued => 'Em fila',
            self::Published => 'Publicada',
            self::Read => 'Lida',
            self::Acknowledged => 'Tomada de conhecimento',
            self::Archived => 'Arquivada',
            self::Cancelled => 'Cancelada',
            self::Expired => 'Expirada',
        };
    }
}
