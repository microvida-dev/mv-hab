<?php

namespace Database\Factories;

use App\Models\ReportDefinition;
use App\Models\ReportFilterPreset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportFilterPreset>
 */
class ReportFilterPresetFactory extends Factory
{
    protected $model = ReportFilterPreset::class;

    public function definition(): array
    {
        return ['report_definition_id' => ReportDefinition::factory(), 'user_id' => User::factory(), 'name' => fake()->words(2, true), 'filters' => ['date_from' => now()->startOfMonth()->toDateString()], 'is_default' => false];
    }
}
