<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MaintenanceAssignmentType: string
{
    use HasOptions;

    case InternalTechnician = 'internal_technician';
    case ExternalSupplier = 'external_supplier';
    case Team = 'team';
    case Unassigned = 'unassigned';

    public function label(): string
    {
        return match ($this) {
            self::InternalTechnician => 'Técnico interno',
            self::ExternalSupplier => 'Fornecedor externo',
            self::Team => 'Equipa',
            self::Unassigned => 'Sem atribuição',
        };
    }
}
