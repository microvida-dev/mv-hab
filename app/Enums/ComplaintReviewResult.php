<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ComplaintReviewResult: string
{
    use HasOptions;

    case Accepted = 'accepted';
    case PartiallyAccepted = 'partially_accepted';
    case Rejected = 'rejected';
    case RequiresAdditionalInformation = 'requires_additional_information';
    case NotAdmissible = 'not_admissible';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Accepted => 'Aceite',
            self::PartiallyAccepted => 'Parcialmente aceite',
            self::Rejected => 'Indeferida',
            self::RequiresAdditionalInformation => 'Requer informação complementar',
            self::NotAdmissible => 'Não admissível',
            self::Withdrawn => 'Desistida',
        };
    }
}
