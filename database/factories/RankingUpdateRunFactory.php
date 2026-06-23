<?php

namespace Database\Factories;

use App\Enums\RankingUpdateStatus;
use App\Models\Contest;
use App\Models\LotteryDraw;
use App\Models\RankingUpdateRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RankingUpdateRun> */
class RankingUpdateRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lottery_run_id' => LotteryDraw::factory(),
            'contest_id' => Contest::factory(),
            'status' => RankingUpdateStatus::Applied->value,
            'before_snapshot' => [],
            'after_snapshot' => [],
            'summary' => ['source' => 'factory'],
            'applied_at' => now(),
        ];
    }
}
