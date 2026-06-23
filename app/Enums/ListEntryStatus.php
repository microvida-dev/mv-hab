<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ListEntryStatus: string
{
    use HasOptions;

    case Admitted = 'admitted';
    case Excluded = 'excluded';
    case Ranked = 'ranked';
    case PendingReview = 'pending_review';
    case ChangedAfterComplaint = 'changed_after_complaint';
    case Removed = 'removed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Admitted => 'Admitida',
            self::Excluded => 'Excluída',
            self::Ranked => 'Classificada',
            self::PendingReview => 'Pendente de revisão',
            self::ChangedAfterComplaint => 'Alterada após reclamação',
            self::Removed => 'Removida',
            self::Cancelled => 'Cancelada',
        };
    }
}
