<?php

namespace Tests\Concerns;

use App\Enums\FeatureKey;
use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\Municipality;
use App\Models\MunicipalityFeatureEntitlement;
use App\Models\User;

trait InteractsWithMunicipalFeatures
{
    protected function municipalityWithFeatures(
        FeatureKey $feature,
        FeatureKey ...$additionalFeatures,
    ): Municipality {
        $municipality = Municipality::factory()->create();

        return $this->enableMunicipalityFeatures(
            $municipality,
            $feature,
            ...$additionalFeatures,
        );
    }

    protected function enableMunicipalityFeature(
        Municipality $municipality,
        FeatureKey $feature,
    ): Municipality {
        MunicipalityFeatureEntitlement::query()->updateOrCreate(
            [
                'municipality_id' => $municipality->getKey(),
                'feature_key' => $feature->value,
            ],
            ['enabled' => true],
        );

        return $municipality;
    }

    protected function enableMunicipalityFeatures(
        Municipality $municipality,
        FeatureKey ...$features,
    ): Municipality {
        foreach ($features as $feature) {
            $this->enableMunicipalityFeature($municipality, $feature);
        }

        return $municipality;
    }

    protected function assignMunicipality(User $user, Municipality $municipality): User
    {
        $user->forceFill(['municipality_id' => $municipality->getKey()])->save();

        return $user->refresh();
    }

    protected function assignApplicationMunicipality(
        User $user,
        Application $application,
        FeatureKey $feature,
        FeatureKey ...$additionalFeatures,
    ): User {
        $municipality = $application->program()->firstOrFail()->municipality()->firstOrFail();

        $this->enableMunicipalityFeatures(
            $municipality,
            $feature,
            ...$additionalFeatures,
        );

        return $this->assignMunicipality($user, $municipality);
    }

    protected function assignDocumentMunicipality(
        User $user,
        DocumentSubmission $document,
        FeatureKey $feature,
        FeatureKey ...$additionalFeatures,
    ): User {
        $municipality = $this->municipalityWithFeatures(
            $feature,
            ...$additionalFeatures,
        );

        $this->assignMunicipality($user, $municipality);

        if ($document->user !== null) {
            $this->assignMunicipality($document->user, $municipality);
        }

        if ($document->adhesionRegistration?->user !== null) {
            $this->assignMunicipality($document->adhesionRegistration->user, $municipality);
        }

        if ($document->application !== null) {
            $document->application->program()->update([
                'municipality_id' => $municipality->getKey(),
            ]);
        }

        return $user->refresh();
    }
}
