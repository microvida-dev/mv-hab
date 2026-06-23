<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum HouseholdRelationship: string
{
    use HasOptions;

    case Applicant = 'applicant';
    case Spouse = 'spouse';
    case Partner = 'partner';
    case Child = 'child';
    case Parent = 'parent';
    case Sibling = 'sibling';
    case Grandparent = 'grandparent';
    case Grandchild = 'grandchild';
    case OtherRelative = 'other_relative';
    case LegalGuardian = 'legal_guardian';
    case Ward = 'ward';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Applicant => 'Requerente',
            self::Spouse => 'Cônjuge',
            self::Partner => 'Companheiro/a',
            self::Child => 'Filho/a',
            self::Parent => 'Pai ou mãe',
            self::Sibling => 'Irmão/irmã',
            self::Grandparent => 'Avô/avó',
            self::Grandchild => 'Neto/a',
            self::OtherRelative => 'Outro familiar',
            self::LegalGuardian => 'Representante legal',
            self::Ward => 'Pessoa a cargo',
            self::Other => 'Outro',
        };
    }
}
