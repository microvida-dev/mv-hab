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
    /**
     * @param  list<FeatureKey>  $features
     */
    protected function municipalityWithFeatures(array $features = []): Municipality
    {
        $municipality = Municipality::factory()->create();

        return $this->enableMunicipalityFeatures($municipality, $features);
    }

    /**
     * @param  list<FeatureKey>  $features
     */
    protected function enableMunicipalityFeatures(Municipality $municipality, array $features): Municipality
    {

        foreach ($features as $feature) {
            MunicipalityFeatureEntitlement::query()->updateOrCreate(
                [
                    'municipality_id' => $municipality->getKey(),
                    'feature_key' => $feature->value,
                ],
                ['enabled' => true],
            );
        }

        return $municipality;
    }

    protected function assignMunicipality(User $user, Municipality $municipality): User
    {
        $user->forceFill(['municipality_id' => $municipality->getKey()])->save();

        return $user->refresh();
    }

    /**
     * @param  list<FeatureKey>  $features
     */
    protected function assignApplicationMunicipality(
        User $user,
        Application $application,
        array $features,
    ): User {
        $municipality = $application->program()->firstOrFail()->municipality()->firstOrFail();

        $this->enableMunicipalityFeatures($municipality, $features);

        return $this->assignMunicipality($user, $municipality);
    }

    /**
     * @param  list<FeatureKey>  $features
     */
    protected function assignDocumentMunicipality(
        User $user,
        DocumentSubmission $document,
        array $features,
    ): User {
        $municipality = $this->municipalityWithFeatures($features);

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
