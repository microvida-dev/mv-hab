<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ComplaintDecisionResult: string
{
    use HasOptions;

    case Accepted = 'accepted';
    case PartiallyAccepted = 'partially_accepted';
    case Rejected = 'rejected';
    case NotAdmissible = 'not_admissible';
    case Withdrawn = 'withdrawn';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Accepted => 'Aceite',
            self::PartiallyAccepted => 'Parcialmente aceite',
            self::Rejected => 'Indeferida',
            self::NotAdmissible => 'Não admissível',
            self::Withdrawn => 'Desistida',
            self::Cancelled => 'Cancelada',
        };
    }
}
