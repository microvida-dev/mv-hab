<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ImpedimentType: string
{
    use HasOptions;

    case MissingRegistration = 'missing_registration';
    case MissingHouseholdData = 'missing_household_data';
    case MissingIncomeData = 'missing_income_data';
    case MissingHousingSituation = 'missing_housing_situation';
    case IncomeAboveLimit = 'income_above_limit';
    case IncomeBelowRequiredThreshold = 'income_below_required_threshold';
    case HouseholdNotMatchingTypology = 'household_not_matching_typology';
    case ContestClosed = 'contest_closed';
    case ContestNotYetOpen = 'contest_not_yet_open';
    case MissingRequiredDocuments = 'missing_required_documents';
    case ExistingActiveContract = 'existing_active_contract';
    case ExistingActiveApplication = 'existing_active_application';
    case AgeOrResidencyCondition = 'age_or_residency_condition';
    case DataOutdated = 'data_outdated';
    case ManualReviewRequired = 'manual_review_required';

    public function label(): string
    {
        return match ($this) {
            self::MissingRegistration => 'Registo de adesão em falta',
            self::MissingHouseholdData => 'Dados do agregado em falta',
            self::MissingIncomeData => 'Rendimentos em falta',
            self::MissingHousingSituation => 'Situação habitacional em falta',
            self::IncomeAboveLimit => 'Rendimento acima do limite',
            self::IncomeBelowRequiredThreshold => 'Rendimento abaixo do mínimo',
            self::HouseholdNotMatchingTypology => 'Tipologia não adequada',
            self::ContestClosed => 'Concurso encerrado',
            self::ContestNotYetOpen => 'Concurso ainda não aberto',
            self::MissingRequiredDocuments => 'Documentos obrigatórios em falta',
            self::ExistingActiveContract => 'Contrato ativo existente',
            self::ExistingActiveApplication => 'Candidatura ativa existente',
            self::AgeOrResidencyCondition => 'Condição de idade ou residência',
            self::DataOutdated => 'Dados desatualizados',
            self::ManualReviewRequired => 'Análise manual necessária',
        };
    }
}
