<?php

namespace Database\Factories;

use App\Models\PublicPortalSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PublicPortalSetting> */
class PublicPortalSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2, '.'),
            'type' => 'string',
            'value' => ['value' => fake()->sentence()],
            'label' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'is_public' => true,
        ];
    }
}
