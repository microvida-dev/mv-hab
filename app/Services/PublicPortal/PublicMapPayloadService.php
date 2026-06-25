<?php

namespace App\Services\PublicPortal;

use App\Enums\HousingLocationPrecision;
use App\Enums\HousingPublicStatus;
use App\Models\HousingUnit;

class PublicMapPayloadService
{
    /**
     * @return array<string, array<string, string|null>|float|string|null>
     */
    public function marker(HousingUnit $unit): array
    {
        $monthlyRent = $unit->getAttribute('monthly_rent');

        return [
            'reference' => $unit->public_reference ?: $unit->public_slug,
            'title' => $unit->displayTitle(),
            'url' => route('public.housing-units.show', $unit->public_slug),
            'typology' => $unit->typology,
            'monthly_rent' => is_numeric($monthlyRent) ? (float) $monthlyRent : null,
            'public_status' => $this->publicStatusLabel($unit),
            'location' => $unit->publicLocationLabel(),
            'latitude' => $this->coordinate($unit, 'public_latitude'),
            'longitude' => $this->coordinate($unit, 'public_longitude'),
            'precision' => $this->precisionLabel($unit),
            'cluster_key' => $this->clusterKey($unit),
            'popup' => [
                'title' => $unit->displayTitle(),
                'subtitle' => collect([$unit->typology, $unit->publicLocationLabel()])->filter()->join(' · '),
                'status' => $this->publicStatusLabel($unit),
            ],
        ];
    }

    private function coordinate(HousingUnit $unit, string $attribute): ?float
    {
        $value = $unit->getAttribute($attribute);

        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, $this->coordinatePrecision($unit));
    }

    private function coordinatePrecision(HousingUnit $unit): int
    {
        $precision = $this->precision($unit);

        return match ($precision) {
            HousingLocationPrecision::Exact => 6,
            HousingLocationPrecision::Street => 4,
            HousingLocationPrecision::Parish,
            HousingLocationPrecision::Approximate,
            null => 3,
        };
    }

    private function clusterKey(HousingUnit $unit): string
    {
        return collect([$unit->parish, $unit->locality])
            ->filter()
            ->map(fn (string $value): string => str($value)->slug()->toString())
            ->join(':') ?: 'sem-zona';
    }

    private function publicStatusLabel(HousingUnit $unit): ?string
    {
        $status = $unit->getAttribute('public_status');

        if ($status instanceof HousingPublicStatus) {
            return $status->label();
        }

        return is_string($status) && $status !== ''
            ? HousingPublicStatus::tryFrom($status)?->label()
            : null;
    }

    private function precisionLabel(HousingUnit $unit): ?string
    {
        return $this->precision($unit)?->label();
    }

    private function precision(HousingUnit $unit): ?HousingLocationPrecision
    {
        $precision = $unit->getAttribute('public_location_precision');

        if ($precision instanceof HousingLocationPrecision) {
            return $precision;
        }

        return is_string($precision) && $precision !== ''
            ? HousingLocationPrecision::tryFrom($precision)
            : null;
    }
}
