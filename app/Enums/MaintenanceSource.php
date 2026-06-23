<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MaintenanceSource: string
{
    use HasOptions;

    case Tenant = 'tenant';
    case MunicipalTechnician = 'municipal_technician';
    case Inspection = 'inspection';
    case System = 'system';
    case Supplier = 'supplier';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Tenant => 'Arrendatário',
            self::MunicipalTechnician => 'Técnico municipal',
            self::Inspection => 'Vistoria',
            self::System => 'Sistema',
            self::Supplier => 'Fornecedor',
            self::Other => 'Outra',
        };
    }
}
