<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InconsistencyType: string
{
    use HasOptions;

    case HouseholdSizeChanged = 'household_size_changed';
    case IncomeChanged = 'income_changed';
    case HousingSituationChanged = 'housing_situation_changed';
    case PreferredTypologyChanged = 'preferred_typology_changed';
    case PreferredParishChanged = 'preferred_parish_changed';
    case DocumentsMissing = 'documents_missing';
    case EligibilityResultChanged = 'eligibility_result_changed';
    case RentEstimateChanged = 'rent_estimate_changed';
    case ContestNoLongerMatching = 'contest_no_longer_matching';
    case SimulationOutdated = 'simulation_outdated';

    public function label(): string
    {
        return match ($this) {
            self::HouseholdSizeChanged => 'Composição do agregado alterada',
            self::IncomeChanged => 'Rendimento alterado',
            self::HousingSituationChanged => 'Situação habitacional alterada',
            self::PreferredTypologyChanged => 'Tipologia preferida alterada',
            self::PreferredParishChanged => 'Freguesia preferida alterada',
            self::DocumentsMissing => 'Documentos em falta',
            self::EligibilityResultChanged => 'Resultado de elegibilidade alterado',
            self::RentEstimateChanged => 'Estimativa de renda alterada',
            self::ContestNoLongerMatching => 'Concurso deixou de corresponder',
            self::SimulationOutdated => 'Simulação desatualizada',
        };
    }
}
