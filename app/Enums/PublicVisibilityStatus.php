<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PublicVisibilityStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case ReadyForReview = 'ready_for_review';
    case Published = 'published';
    case Hidden = 'hidden';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::ReadyForReview => 'Pronto para revisão',
            self::Published => 'Publicado',
            self::Hidden => 'Oculto',
            self::Archived => 'Arquivado',
        };
    }
}
