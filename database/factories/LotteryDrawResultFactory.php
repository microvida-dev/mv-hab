<?php

namespace Database\Factories;

use App\Enums\LotteryResultType;
use App\Models\Application;
use App\Models\ContestHousingUnit;
use App\Models\HousingUnit;
use App\Models\LotteryDrawResult;
use App\Models\LotteryParticipant;
use App\Models\LotteryRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LotteryDrawResult> */
class LotteryDrawResultFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lottery_run_id' => LotteryRun::factory(),
            'lottery_participant_id' => LotteryParticipant::factory(),
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'draw_order' => 1,
            'result_type' => LotteryResultType::Selected->value,
            'selected' => true,
            'assigned_contest_housing_unit_id' => ContestHousingUnit::factory(),
            'assigned_housing_unit_id' => HousingUnit::factory(),
            'random_value' => hash('sha256', fake()->uuid()),
            'audit_data' => ['source' => 'factory'],
        ];
    }
}
