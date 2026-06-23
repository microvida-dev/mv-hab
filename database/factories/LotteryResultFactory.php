<?php

namespace Database\Factories;

use App\Enums\LotteryResultStatus;
use App\Enums\LotteryResultType;
use App\Models\Application;
use App\Models\LotteryDraw;
use App\Models\LotteryParticipant;
use App\Models\LotteryResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LotteryResult> */
class LotteryResultFactory extends Factory
{
    protected $model = LotteryResult::class;

    public function definition(): array
    {
        return [
            'lottery_run_id' => LotteryDraw::factory(),
            'lottery_participant_id' => LotteryParticipant::factory(),
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'draw_order' => 1,
            'result_type' => LotteryResultType::Selected->value,
            'status' => LotteryResultStatus::Generated->value,
            'selected' => true,
            'random_value' => hash('sha256', fake()->uuid()),
            'result_hash' => hash('sha256', fake()->uuid()),
            'audit_data' => ['source' => 'factory'],
        ];
    }
}
