<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContractSignatureRole: string
{
    use HasOptions;

    case Tenant = 'tenant';
    case Landlord = 'landlord';
    case Representative = 'representative';
    case Witness = 'witness';
    case InternalValidator = 'internal_validator';

    public function label(): string
    {
        return match ($this) {
            self::Tenant => 'Arrendatário',
            self::Landlord => 'Senhorio',
            self::Representative => 'Representante',
            self::Witness => 'Testemunha',
            self::InternalValidator => 'Validação interna',
        };
    }
}
