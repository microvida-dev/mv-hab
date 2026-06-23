<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PaymentReceiptStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Issued = 'issued';
    case Cancelled = 'cancelled';
    case Reissued = 'reissued';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Issued => 'Emitido',
            self::Cancelled => 'Cancelado',
            self::Reissued => 'Reemitido',
            self::Archived => 'Arquivado',
        };
    }
}
