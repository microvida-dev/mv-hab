<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AnnualIncomeLimitStatus: string
{
    use HasOptions;

    case Configured = 'configured';
    case ConfigurationIncomplete = 'configuration_incomplete';

    public function label(): string
    {
        return match ($this) {
            self::Configured => 'Configurado',
            self::ConfigurationIncomplete => 'Configuração incompleta',
        };
    }
}
