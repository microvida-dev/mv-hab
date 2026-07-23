<?php

namespace Tests\Concerns;

use App\Enums\FeatureKey;
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

        foreach ($features as $feature) {
            MunicipalityFeatureEntitlement::factory()
                ->enabled()
                ->forFeature($feature)
                ->create(['municipality_id' => $municipality->getKey()]);
        }

        return $municipality;
    }

    protected function assignMunicipality(User $user, Municipality $municipality): User
    {
        $user->forceFill(['municipality_id' => $municipality->getKey()])->save();

        return $user->refresh();
    }
}
