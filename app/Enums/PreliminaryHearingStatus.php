<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PreliminaryHearingStatus: string
{
    use HasOptions;

    case NotApplicable = 'not_applicable';
    case Open = 'open';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case PartiallyAccepted = 'partially_accepted';
    case Closed = 'closed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::NotApplicable => 'Não aplicável',
            self::Open => 'Aberta',
            self::Submitted => 'Submetida',
            self::UnderReview => 'Em análise',
            self::Accepted => 'Aceite',
            self::Rejected => 'Rejeitada',
            self::PartiallyAccepted => 'Parcialmente aceite',
            self::Closed => 'Fechada',
            self::Expired => 'Expirada',
        };
    }
}
