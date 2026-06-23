<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentCategory: string
{
    use HasOptions;

    case Identification = 'identification';
    case Tax = 'tax';
    case SocialSecurity = 'social_security';
    case Income = 'income';
    case Housing = 'housing';
    case Household = 'household';
    case Health = 'health';
    case Education = 'education';
    case Employment = 'employment';
    case Declaration = 'declaration';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Identification => 'Identificação',
            self::Tax => 'Fiscal',
            self::SocialSecurity => 'Segurança Social',
            self::Income => 'Rendimentos',
            self::Housing => 'Habitação',
            self::Household => 'Agregado',
            self::Health => 'Saúde',
            self::Education => 'Educação',
            self::Employment => 'Emprego',
            self::Declaration => 'Declaração',
            self::Other => 'Outro',
        };
    }
}
