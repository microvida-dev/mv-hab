<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AdministrativeDecisionType: string
{
    use HasOptions;

    case AdmissionForScoring = 'admission_for_scoring';
    case NonAdmission = 'non_admission';
    case CorrectionOutcome = 'correction_outcome';
    case AdministrativeClosure = 'administrative_closure';

    public function label(): string
    {
        return match ($this) {
            self::AdmissionForScoring => 'Admissão para classificação',
            self::NonAdmission => 'Não admissão',
            self::CorrectionOutcome => 'Resultado de aperfeiçoamento',
            self::AdministrativeClosure => 'Encerramento administrativo',
        };
    }
}
