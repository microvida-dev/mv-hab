<?php

namespace App\Services\Applications;

use App\Enums\ApplicationSnapshotType;
use App\Enums\ApplicationStatus;
use App\Enums\HousingCompatibilityStatus;
use App\Models\Application;
use App\Models\ApplicationPreference;
use App\Models\HousingPreference;

class HousingPreferenceSnapshotService
{
    public function __construct(
        private readonly ApplicationHousingPreferenceSourceResolver $source,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function forApplication(Application $application): array
    {
        if ($application->status !== ApplicationStatus::Draft) {
            $snapshot = $application->relationLoaded('snapshots')
                ? $application->snapshots->firstWhere(
                    'snapshot_type',
                    ApplicationSnapshotType::HousingPreferences,
                )
                : $application->snapshots()
                    ->where(
                        'snapshot_type',
                        ApplicationSnapshotType::HousingPreferences->value,
                    )
                    ->first();
            $snapshotData = $snapshot?->getAttribute('data');

            return $this->normalizeSnapshotData($snapshotData);
        }

        return $this->liveForApplication($application);
    }

    /**
     * Builds a transient preview. It never persists the administrative snapshot.
     *
     * @return list<array<string, mixed>>
     */
    public function liveForApplication(Application $application): array
    {
        $snapshot = [];

        foreach ($this->source->preferencesFor($application)
            ->sortBy('preference_order')
            ->values() as $preference) {
            $official = $preference instanceof HousingPreference;
            $legacy = $preference instanceof ApplicationPreference;
            $snapshot[] = [
                'preference_order' => $preference->preference_order,
                'contest_housing_unit_id' => $official
                    ? $preference->contest_housing_unit_id
                    : null,
                'housing_unit_id' => $preference->housing_unit_id,
                'code' => $preference->housingUnit?->code,
                'public_reference' => $preference->housingUnit?->public_reference,
                'public_title' => $preference->housingUnit?->public_title,
                'typology' => $preference->housingUnit?->typology,
                'monthly_rent' => $official
                    ? data_get(
                        $preference->compatibility_snapshot,
                        'monthly_rent',
                        $preference->housingUnit?->monthly_rent,
                    )
                    : $preference->housingUnit?->monthly_rent,
                'compatibility_status' => $official
                    ? $this->statusValue($preference->compatibility_status)
                    : HousingCompatibilityStatus::RequiresRevalidation->value,
                'compatibility_snapshot' => $official
                    ? $preference->compatibility_snapshot
                    : null,
                'regulatory_snapshot_id' => $official
                    ? $preference->regulatory_snapshot_id
                    : null,
                'evaluated_at' => $official
                    ? $preference->evaluated_at?->toIso8601String()
                    : null,
                'submitted_at' => $official
                    ? $preference->submitted_at?->toIso8601String()
                    : null,
                'locked_at' => $official
                    ? $preference->locked_at?->toIso8601String()
                    : null,
                'source' => $legacy
                    ? 'application_preferences_legacy'
                    : 'housing_preferences',
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

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeSnapshotData(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        $rows = [];

        foreach ($data as $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalized = [];

            foreach ($row as $key => $value) {
                if (is_string($key)) {
                    $normalized[$key] = $value;
                }
            }

            $rows[] = $normalized;
        }

        return $rows;
    }
}
