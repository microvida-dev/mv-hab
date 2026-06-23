<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentDossierStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Complete = 'complete';
    case Incomplete = 'incomplete';
    case RequiresReview = 'requires_review';
    case Standardized = 'standardized';
    case Exported = 'exported';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Complete => 'Completo',
            self::Incomplete => 'Incompleto',
            self::RequiresReview => 'Requer revisão',
            self::Standardized => 'Padronizado',
            self::Exported => 'Exportado',
            self::Archived => 'Arquivado',
        };
    }
}
