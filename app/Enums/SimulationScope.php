<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum SimulationScope: string
{
    use HasOptions;

    case Anonymous = 'anonymous';
    case Authenticated = 'authenticated';
    case RegistrationRenewal = 'registration_renewal';
    case ApplicationPrefill = 'application_prefill';

    public function label(): string
    {
        return match ($this) {
            self::Anonymous => 'Simulação anónima',
            self::Authenticated => 'Simulação autenticada',
            self::RegistrationRenewal => 'Renovação de registo',
            self::ApplicationPrefill => 'Pré-preenchimento',
        };
    }
}
