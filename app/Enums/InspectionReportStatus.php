<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InspectionReportStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Generated = 'generated';
    case Validated = 'validated';
    case Issued = 'issued';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Generated => 'Gerado',
            self::Validated => 'Validado',
            self::Issued => 'Emitido',
            self::Cancelled => 'Cancelado',
            self::Archived => 'Arquivado',
        };
    }
}
