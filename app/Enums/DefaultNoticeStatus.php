<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DefaultNoticeStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Issued = 'issued';
    case SentInternal = 'sent_internal';
    case Acknowledged = 'acknowledged';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Issued => 'Emitido',
            self::SentInternal => 'Enviado internamente',
            self::Acknowledged => 'Tomado conhecimento',
            self::Cancelled => 'Cancelado',
            self::Archived => 'Arquivado',
        };
    }
}
