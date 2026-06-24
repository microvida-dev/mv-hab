<?php

namespace Database\Factories;

use App\Models\MunicipalTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<MunicipalTeam> */
class MunicipalTeamFactory extends Factory
{
    protected $model = MunicipalTeam::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->sentence(),
            'status' => 'active',
            'functional_scopes' => ['backoffice'],
            'manager_user_id' => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
