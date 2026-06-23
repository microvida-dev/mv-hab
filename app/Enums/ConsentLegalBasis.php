<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ConsentLegalBasis: string
{
    use HasOptions;

    case Consent = 'consent';
    case Contract = 'contract';
    case LegalObligation = 'legal_obligation';
    case PublicInterest = 'public_interest';
    case LegitimateInterest = 'legitimate_interest';
    case VitalInterest = 'vital_interest';

    public function label(): string
    {
        return match ($this) {
            self::Consent => 'Consentimento',
            self::Contract => 'Execução contratual',
            self::LegalObligation => 'Obrigação legal',
            self::PublicInterest => 'Interesse público',
            self::LegitimateInterest => 'Interesse legítimo',
            self::VitalInterest => 'Interesse vital',
        };
    }
}
