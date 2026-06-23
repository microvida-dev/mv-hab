<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ProfessionalStatus: string
{
    use HasOptions;

    case Employed = 'employed';
    case SelfEmployed = 'self_employed';
    case Unemployed = 'unemployed';
    case Student = 'student';
    case Retired = 'retired';
    case Pensioner = 'pensioner';
    case Disabled = 'disabled';
    case DomesticWork = 'domestic_work';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Employed => 'Trabalhador por conta de outrem',
            self::SelfEmployed => 'Trabalhador independente',
            self::Unemployed => 'Desempregado',
            self::Student => 'Estudante',
            self::Retired => 'Reformado',
            self::Pensioner => 'Pensionista',
            self::Disabled => 'Incapacidade para o trabalho',
            self::DomesticWork => 'Trabalho doméstico',
            self::Other => 'Outra situação',
        };
    }
}
