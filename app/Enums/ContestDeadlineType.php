<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ContestDeadlineType: string
{
    use HasOptions;

    case Applications = 'applications';
    case Corrections = 'corrections';
    case Complaints = 'complaints';
    case Hearing = 'hearing';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Applications => 'Candidaturas',
            self::Corrections => 'Aperfeiçoamento',
            self::Complaints => 'Reclamações',
            self::Hearing => 'Audiência de interessados',
            self::Other => 'Outro prazo',
        };
    }
}
