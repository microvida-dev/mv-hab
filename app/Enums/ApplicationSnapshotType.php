<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ApplicationSnapshotType: string
{
    use HasOptions;

    case AdhesionRegistration = 'adhesion_registration';
    case Household = 'household';
    case HouseholdMembers = 'household_members';
    case IncomeRecords = 'income_records';
    case CurrentHousingSituation = 'current_housing_situation';
    case Documents = 'documents';
    case Summary = 'summary';

    public function label(): string
    {
        return match ($this) {
            self::AdhesionRegistration => 'Registo de Adesão',
            self::Household => 'Agregado familiar',
            self::HouseholdMembers => 'Membros do agregado',
            self::IncomeRecords => 'Rendimentos',
            self::CurrentHousingSituation => 'Situação habitacional',
            self::Documents => 'Documentos',
            self::Summary => 'Resumo',
        };
    }
}
