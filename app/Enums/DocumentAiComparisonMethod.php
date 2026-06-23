<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentAiComparisonMethod: string
{
    use HasOptions;

    case Exact = 'exact';
    case NormalizedExact = 'normalized_exact';
    case FuzzyName = 'fuzzy_name';
    case Date = 'date';
    case MoneyTolerance = 'money_tolerance';
    case AddressSimilarity = 'address_similarity';
    case DocumentConsistency = 'document_consistency';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Exact => 'Exata',
            self::NormalizedExact => 'Exata normalizada',
            self::FuzzyName => 'Nome aproximado',
            self::Date => 'Data',
            self::MoneyTolerance => 'Valor com tolerância',
            self::AddressSimilarity => 'Morada aproximada',
            self::DocumentConsistency => 'Consistência documental',
            self::Manual => 'Manual',
        };
    }
}
