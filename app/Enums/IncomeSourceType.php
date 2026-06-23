<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum IncomeSourceType: string
{
    use HasOptions;

    case Employment = 'employment';
    case SelfEmployment = 'self_employment';
    case Pension = 'pension';
    case UnemploymentBenefit = 'unemployment_benefit';
    case SocialBenefit = 'social_benefit';
    case Property = 'property';
    case Capital = 'capital';
    case Scholarship = 'scholarship';
    case HousingSupport = 'housing_support';
    case Other = 'other';
    case NoIncome = 'no_income';

    public function label(): string
    {
        return match ($this) {
            self::Employment => 'Trabalho dependente',
            self::SelfEmployment => 'Trabalho independente',
            self::Pension => 'Pensões',
            self::UnemploymentBenefit => 'Subsídio de desemprego',
            self::SocialBenefit => 'Prestações sociais',
            self::Property => 'Rendimentos prediais',
            self::Capital => 'Rendimentos de capitais',
            self::Scholarship => 'Bolsas',
            self::HousingSupport => 'Apoios habitacionais',
            self::Other => 'Outros rendimentos',
            self::NoIncome => 'Sem rendimentos',
        };
    }
}
