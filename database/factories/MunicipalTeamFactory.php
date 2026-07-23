<?php

namespace Database\Factories;

use App\Models\Municipality;
use App\Models\MunicipalTeam;
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
            'municipality_id' => fn (): int => (int) (
                Municipality::query()->value('id')
                ?? Municipality::factory()->create()->id
            ),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->sentence(),
            'status' => 'active',
            'functional_scopes' => ['backoffice'],
            'manager_user_id' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }
}
