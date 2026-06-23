<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentDossierItemStatus: string
{
    use HasOptions;

    case Missing = 'missing';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Validated = 'validated';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Duplicate = 'duplicate';

    public function label(): string
    {
        return match ($this) {
            self::Missing => 'Em falta',
            self::Submitted => 'Submetido',
            self::UnderReview => 'Em análise',
            self::Validated => 'Validado',
            self::Rejected => 'Rejeitado',
            self::Expired => 'Expirado',
            self::Duplicate => 'Duplicado',
        };
    }
}
