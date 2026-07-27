<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum HousingCompatibilityStatus: string
{
    use HasOptions;

    case Compatible = 'compatible';
    case Incompatible = 'incompatible';
    case RequiresData = 'requires_data';
    case RequiresManualReview = 'requires_manual_review';
    case ConfigurationIncomplete = 'configuration_incomplete';
    case RequiresRevalidation = 'requires_revalidation';

    public function label(): string
    {
        return match ($this) {
            self::Compatible => 'Compatível',
            self::Incompatible => 'Não compatível',
            self::RequiresData => 'Dados em falta',
            self::RequiresManualReview => 'Requer revisão',
            self::ConfigurationIncomplete => 'Configuração incompleta',
            self::RequiresRevalidation => 'Requer nova validação',
        };
    }

    public function isSelectable(): bool
    {
        return $this === self::Compatible;
    }
}
