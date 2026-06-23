<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EligibilityCriterionCategory: string
{
    use HasOptions;

    case Identity = 'identity';
    case Residence = 'residence';
    case Household = 'household';
    case Income = 'income';
    case Housing = 'housing';
    case Documents = 'documents';
    case Application = 'application';
    case LegalImpediments = 'legal_impediments';
    case Typology = 'typology';
    case SpecialCondition = 'special_condition';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Identity => 'Identificação',
            self::Residence => 'Residência e atividade',
            self::Household => 'Agregado',
            self::Income => 'Rendimentos',
            self::Housing => 'Situação habitacional',
            self::Documents => 'Documentos',
            self::Application => 'Candidatura',
            self::LegalImpediments => 'Impedimentos legais',
            self::Typology => 'Tipologia',
            self::SpecialCondition => 'Condição especial',
            self::Other => 'Outro',
        };
    }
}
