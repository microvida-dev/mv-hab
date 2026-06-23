<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum IndicatorCategory: string
{
    use HasOptions;

    case Applications = 'applications';
    case Eligibility = 'eligibility';
    case Documents = 'documents';
    case Complaints = 'complaints';
    case Housing = 'housing';
    case Allocation = 'allocation';
    case Contracts = 'contracts';
    case Finance = 'finance';
    case Maintenance = 'maintenance';
    case Communications = 'communications';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Applications => 'Candidaturas',
            self::Eligibility => 'Elegibilidade',
            self::Documents => 'Documentos',
            self::Complaints => 'Reclamações',
            self::Housing => 'Habitação',
            self::Allocation => 'Atribuição',
            self::Contracts => 'Contratos',
            self::Finance => 'Financeiro',
            self::Maintenance => 'Manutenção',
            self::Communications => 'Comunicações',
            self::System => 'Sistema',
        };
    }
}
