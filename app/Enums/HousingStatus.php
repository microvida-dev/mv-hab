<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum HousingStatus: string
{
    use HasOptions;

    case Rented = 'rented';
    case Owned = 'owned';
    case FamilyHome = 'family_home';
    case Ceded = 'ceded';
    case Temporary = 'temporary';
    case Homeless = 'homeless';
    case Institutional = 'institutional';
    case EmployerProvided = 'employer_provided';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Rented => 'Habitação arrendada',
            self::Owned => 'Habitação própria',
            self::FamilyHome => 'Casa de familiares',
            self::Ceded => 'Habitação cedida',
            self::Temporary => 'Alojamento temporário',
            self::Homeless => 'Sem habitação',
            self::Institutional => 'Resposta institucional',
            self::EmployerProvided => 'Habitação disponibilizada pela entidade patronal',
            self::Other => 'Outra situação',
        };
    }
}
