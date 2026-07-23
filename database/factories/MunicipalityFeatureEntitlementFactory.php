<?php

namespace Database\Factories;

use App\Enums\FeatureKey;
use App\Models\Municipality;
use App\Models\MunicipalityFeatureEntitlement;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MunicipalityFeatureEntitlement> */
class MunicipalityFeatureEntitlementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'municipality_id' => Municipality::factory(),
            'feature_key' => FeatureKey::ApplicationIntake,
            'enabled' => false,
        ];
    }

    public function enabled(): static
    {
        return $this->state(fn (): array => ['enabled' => true]);
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => ['enabled' => false]);
    }

    public function forFeature(FeatureKey $feature): static
    {
        return $this->state(fn (): array => ['feature_key' => $feature]);
    }
}
