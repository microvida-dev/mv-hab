<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AllocationReportStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Generated = 'generated';
    case Approved = 'approved';
    case Published = 'published';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Generated => 'Gerado',
            self::Approved => 'Aprovado',
            self::Published => 'Publicado',
            self::Cancelled => 'Cancelado',
            self::Archived => 'Arquivado',
        };
    }
}
