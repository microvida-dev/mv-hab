<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContractDocumentStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Generated = 'generated';
    case Issued = 'issued';
    case Signed = 'signed';
    case Archived = 'archived';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Generated => 'Gerado',
            self::Issued => 'Emitido',
            self::Signed => 'Assinado',
            self::Archived => 'Arquivado',
            self::Cancelled => 'Cancelado',
        };
    }
}
