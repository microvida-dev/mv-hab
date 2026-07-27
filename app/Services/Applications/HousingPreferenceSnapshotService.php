<?php

namespace App\Services\Applications;

use App\Enums\HousingCompatibilityStatus;
use App\Models\Application;

class HousingPreferenceSnapshotService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function forApplication(Application $application): array
    {
        $application->loadMissing([
            'housingPreferences.housingUnit',
            'preferences.housingUnit',
        ]);

        if ($application->housingPreferences->isNotEmpty()) {
            $snapshot = [];

            foreach (
                $application->housingPreferences
                    ->sortBy('preference_order')
                    ->values() as $preference
            ) {
                $snapshot[] = [
                    'preference_order' => $preference->preference_order,
                    'contest_housing_unit_id' => $preference->contest_housing_unit_id,
                    'housing_unit_id' => $preference->housing_unit_id,
                    'public_reference' => $preference->housingUnit?->public_reference,
                    'public_title' => $preference->housingUnit?->public_title,
                    'typology' => $preference->housingUnit?->typology,
                    'monthly_rent' => data_get(
                        $preference->compatibility_snapshot,
                        'monthly_rent',
                        $preference->housingUnit?->monthly_rent,
                    ),
                    'compatibility_status' => $this->statusValue(
                        $preference->compatibility_status,
                    ),
                    'compatibility_snapshot' => $preference->compatibility_snapshot,
                    'regulatory_snapshot_id' => $preference->regulatory_snapshot_id,
                    'evaluated_at' => $preference->evaluated_at?->toIso8601String(),
                    'submitted_at' => $preference->submitted_at?->toIso8601String(),
                    'locked_at' => $preference->locked_at?->toIso8601String(),
                    'source' => 'housing_preferences',
                ];
            }

            return $snapshot;
        }

        $snapshot = [];

        foreach (
            $application->preferences
                ->sortBy('preference_order')
                ->values() as $preference
        ) {
            $snapshot[] = [
                'preference_order' => $preference->preference_order,
                'contest_housing_unit_id' => null,
                'housing_unit_id' => $preference->housing_unit_id,
                'public_reference' => $preference->housingUnit?->public_reference,
                'public_title' => $preference->housingUnit?->public_title,
                'typology' => $preference->housingUnit?->typology,
                'monthly_rent' => $preference->housingUnit?->monthly_rent,
                'compatibility_status' => HousingCompatibilityStatus::RequiresRevalidation->value,
                'compatibility_snapshot' => null,
                'regulatory_snapshot_id' => null,
                'evaluated_at' => null,
                'submitted_at' => null,
                'locked_at' => null,
                'source' => 'application_preferences_legacy',
            ];
        }

        return $snapshot;
    }

    private function statusValue(mixed $status): ?string
    {
        if ($status instanceof HousingCompatibilityStatus) {
            return $status->value;
        }

        return is_string($status) ? $status : null;
    }
}
