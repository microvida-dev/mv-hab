<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ProvisionalListStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Published = 'published';
    case ComplaintPeriodOpen = 'complaint_period_open';
    case ComplaintPeriodClosed = 'complaint_period_closed';
    case Superseded = 'superseded';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::UnderReview => 'Em revisão',
            self::Approved => 'Aprovada',
            self::Published => 'Publicada',
            self::ComplaintPeriodOpen => 'Prazo de reclamação aberto',
            self::ComplaintPeriodClosed => 'Prazo de reclamação fechado',
            self::Superseded => 'Substituída',
            self::Cancelled => 'Cancelada',
            self::Archived => 'Arquivada',
        };
    }
}
