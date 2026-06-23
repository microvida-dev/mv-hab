<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContractPartyType: string
{
    use HasOptions;

    case Tenant = 'tenant';
    case CoTenant = 'co_tenant';
    case Landlord = 'landlord';
    case Representative = 'representative';
    case Guarantor = 'guarantor';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Tenant => 'Arrendatário',
            self::CoTenant => 'Coarrendatário',
            self::Landlord => 'Senhorio',
            self::Representative => 'Representante',
            self::Guarantor => 'Fiador',
            self::Other => 'Outro',
        };
    }
}
