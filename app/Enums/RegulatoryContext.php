<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum RegulatoryContext: string
{
    use HasOptions;

    case ProgramPublication = 'program_publication';
    case ContestPublication = 'contest_publication';
    case ApplicationSubmission = 'application_submission';
    case EligibilityCalculation = 'eligibility_calculation';
    case RentCalculation = 'rent_calculation';
    case ContractExecution = 'contract_execution';

    public function label(): string
    {
        return match ($this) {
            self::ProgramPublication => 'Publicação do programa',
            self::ContestPublication => 'Publicação do concurso',
            self::ApplicationSubmission => 'Submissão da candidatura',
            self::EligibilityCalculation => 'Cálculo de elegibilidade',
            self::RentCalculation => 'Cálculo de renda',
            self::ContractExecution => 'Execução contratual',
        };
    }
}
