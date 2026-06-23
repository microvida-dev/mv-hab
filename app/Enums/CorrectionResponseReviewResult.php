<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CorrectionResponseReviewResult: string
{
    use HasOptions;

    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case RequiresMoreInformation = 'requires_more_information';
    case NotApplicable = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::Accepted => 'Aceite',
            self::Rejected => 'Rejeitada',
            self::RequiresMoreInformation => 'Requer mais informação',
            self::NotApplicable => 'Não aplicável',
        };
    }
}
