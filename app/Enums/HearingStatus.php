<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum HearingStatus: string
{
    use HasOptions;

    case NotRequired = 'not_required';
    case Draft = 'draft';
    case Issued = 'issued';
    case Open = 'open';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'Não aplicável',
            self::Draft => 'Rascunho',
            self::Issued => 'Emitida',
            self::Open => 'Aberta',
            self::Submitted => 'Pronúncia submetida',
            self::UnderReview => 'Em análise',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
            self::Closed => 'Fechada',
        };
    }
}
