<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DefinitiveListStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Published = 'published';
    case Locked = 'locked';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::UnderReview => 'Em revisão',
            self::Approved => 'Aprovada',
            self::Published => 'Publicada',
            self::Locked => 'Bloqueada',
            self::Cancelled => 'Cancelada',
            self::Archived => 'Arquivada',
        };
    }
}
