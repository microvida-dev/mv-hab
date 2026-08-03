<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RegulatoryClassificationStatus: string
{
    use HasOptions;

    case Unclassified = 'unclassified';
    case Configured = 'configured';
    case RequiresManualReview = 'requires_manual_review';

    public function label(): string
    {
        return match ($this) {
            self::Unclassified => 'Não classificado',
            self::Configured => 'Configurado',
            self::RequiresManualReview => 'Requer revisão manual',
        };
    }
}
