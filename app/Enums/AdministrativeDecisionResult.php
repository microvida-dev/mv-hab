<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AdministrativeDecisionResult: string
{
    use HasOptions;

    case AdmittedForScoring = 'admitted_for_scoring';
    case NotAdmitted = 'not_admitted';
    case RequiresCorrection = 'requires_correction';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::AdmittedForScoring => 'Admitido para classificação',
            self::NotAdmitted => 'Não admitido',
            self::RequiresCorrection => 'Requer aperfeiçoamento',
            self::Closed => 'Fechado',
            self::Cancelled => 'Cancelado',
        };
    }
}
