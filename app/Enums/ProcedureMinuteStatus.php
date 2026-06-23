<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ProcedureMinuteStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Generated = 'generated';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Archived = 'archived';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Generated => 'Gerada',
            self::UnderReview => 'Em revisão',
            self::Approved => 'Aprovada',
            self::Archived => 'Arquivada',
            self::Cancelled => 'Cancelada',
        };
    }
}
