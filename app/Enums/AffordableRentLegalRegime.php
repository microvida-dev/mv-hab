<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

enum AffordableRentLegalRegime: string
{
    use HasOptions;

    case PaaLegacy2019 = 'paa_legacy_2019';
    case Rsaa2026 = 'rsaa_2026';

    public function label(): string
    {
        return match ($this) {
            self::PaaLegacy2019 => 'Programa de Arrendamento Acessível (regime legacy)',
            self::Rsaa2026 => 'Regime do Arrendamento Acessível',
        };
    }

    public static function forReferenceDate(CarbonInterface $referenceDate): self
    {
        $localDate = CarbonImmutable::instance($referenceDate)
            ->setTimezone('Europe/Lisbon')
            ->startOfDay();

        return $localDate->lessThan(CarbonImmutable::create(2026, 9, 1, 0, 0, 0, 'Europe/Lisbon'))
            ? self::PaaLegacy2019
            : self::Rsaa2026;
    }
}
