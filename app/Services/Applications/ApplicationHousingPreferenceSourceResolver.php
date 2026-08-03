<?php

namespace App\Services\Applications;

use App\Enums\ApplicationPreferenceSource;
use App\Models\Application;
use App\Models\ApplicationPreference;
use App\Models\HousingPreference;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class ApplicationHousingPreferenceSourceResolver
{
    public function source(Application $application): ApplicationPreferenceSource
    {
        $source = $application->getAttribute('preference_source');

        return $source instanceof ApplicationPreferenceSource
            ? $source
            : ApplicationPreferenceSource::tryFrom((string) $source)
                ?? ApplicationPreferenceSource::Uninitialized;
    }

    /**
     * @return Collection<int, HousingPreference|ApplicationPreference>
     */
    public function preferencesFor(Application $application): Collection
    {
        $source = $this->source($application);

        if ($source->isOfficial()) {
            $application->loadMissing([
                'housingPreferences.contestHousingUnit',
                'housingPreferences.housingUnit',
            ]);
        } elseif ($source === ApplicationPreferenceSource::Legacy) {
            $application->loadMissing('preferences.housingUnit');
        }

        $preferences = match ($source) {
            ApplicationPreferenceSource::Official,
            ApplicationPreferenceSource::Reconciled => $application
                ->housingPreferences
                ->all(),
            ApplicationPreferenceSource::Legacy => $application
                ->preferences
                ->all(),
            ApplicationPreferenceSource::Uninitialized,
            ApplicationPreferenceSource::RequiresManualReview => [],
        };

        return collect($preferences)
            ->map(
                static fn (
                    mixed $preference,
                ): HousingPreference|ApplicationPreference => $preference,
            )
            ->values();
    }

    public function markOfficial(
        Application $application,
        ?CarbonInterface $initializedAt = null,
    ): void {
        $source = $this->source($application);

        if ($source === ApplicationPreferenceSource::Reconciled) {
            return;
        }

        $application->forceFill([
            'preference_source' => ApplicationPreferenceSource::Official,
            'official_preferences_initialized_at' => $application
                ->official_preferences_initialized_at
                ?? $initializedAt
                ?? now(),
        ])->save();
    }

    public function markReconciled(
        Application $application,
        ?CarbonInterface $reconciledAt = null,
    ): void {
        $timestamp = $reconciledAt ?? now();

        $application->forceFill([
            'preference_source' => ApplicationPreferenceSource::Reconciled,
            'official_preferences_initialized_at' => $application
                ->official_preferences_initialized_at
                ?? $timestamp,
            'legacy_preferences_reconciled_at' => $timestamp,
        ])->save();
    }

    public function markRequiresManualReview(Application $application): void
    {
        $application->forceFill([
            'preference_source' => ApplicationPreferenceSource::RequiresManualReview,
        ])->save();
    }
}
