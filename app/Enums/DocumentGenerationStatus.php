<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentGenerationStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Generated = 'generated';
    case Issued = 'issued';
    case Cancelled = 'cancelled';
    case Archived = 'archived';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Generated => 'Gerado',
            self::Issued => 'Emitido',
            self::Cancelled => 'Cancelado',
            self::Archived => 'Arquivado',
            self::Failed => 'Falhado',
        };
    }
}
