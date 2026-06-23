<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum GeneratedProcedureDocumentStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Generated = 'generated';
    case Approved = 'approved';
    case Archived = 'archived';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Generated => 'Gerado',
            self::Approved => 'Aprovado',
            self::Archived => 'Arquivado',
            self::Failed => 'Falhou',
        };
    }
}
