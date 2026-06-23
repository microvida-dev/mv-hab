<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentAppliesTo: string
{
    use HasOptions;

    case AdhesionRegistration = 'adhesion_registration';
    case Household = 'household';
    case HouseholdMember = 'household_member';
    case IncomeRecord = 'income_record';
    case CurrentHousingSituation = 'current_housing_situation';
    case Application = 'application';
    case Contract = 'contract';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::AdhesionRegistration => 'Registo de Adesão',
            self::Household => 'Agregado',
            self::HouseholdMember => 'Membro do agregado',
            self::IncomeRecord => 'Rendimento',
            self::CurrentHousingSituation => 'Situação habitacional',
            self::Application => 'Candidatura',
            self::Contract => 'Contrato',
            self::General => 'Geral',
        };
    }
}
