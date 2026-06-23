<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TenantCommunicationVisibility: string
{
    use HasOptions;

    case TenantAndMunicipality = 'tenant_and_municipality';
    case MunicipalityOnly = 'municipality_only';
    case TenantOnly = 'tenant_only';

    public function label(): string
    {
        return match ($this) {
            self::TenantAndMunicipality => 'Inquilino e município',
            self::MunicipalityOnly => 'Apenas município',
            self::TenantOnly => 'Apenas inquilino',
        };
    }
}
