<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ComplaintDecisionStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Proposed = 'proposed';
    case Approved = 'approved';
    case Notified = 'notified';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Proposed => 'Proposta',
            self::Approved => 'Aprovada',
            self::Notified => 'Notificada',
            self::Cancelled => 'Cancelada',
        };
    }
}
