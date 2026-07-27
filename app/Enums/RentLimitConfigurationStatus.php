<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RentLimitConfigurationStatus: string
{
    use HasOptions;

    case Configured = 'configured';
    case Incomplete = 'incomplete';
    case NotApplicable = 'not_applicable';
    case RequiresManualReview = 'requires_manual_review';

    public function label(): string
    {
        return match ($this) {
            self::Configured => 'Configurado',
            self::Incomplete => 'Configuração incompleta',
            self::NotApplicable => 'Não aplicável',
            self::RequiresManualReview => 'Requer revisão manual',
        };
    }
}
