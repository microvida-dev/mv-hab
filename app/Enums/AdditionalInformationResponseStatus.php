<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AdditionalInformationResponseStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Submitted => 'Submetida',
            self::UnderReview => 'Em análise',
            self::Accepted => 'Aceite',
            self::Rejected => 'Rejeitada',
            self::Cancelled => 'Cancelada',
        };
    }
}
