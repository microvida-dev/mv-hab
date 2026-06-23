<?php

namespace Database\Factories;

use App\Enums\LotteryRunStatus;
use App\Models\AllocationRun;
use App\Models\Contest;
use App\Models\DefinitiveList;
use App\Models\LotteryRun;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LotteryRun> */
class LotteryRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'allocation_run_id' => AllocationRun::factory(),
            'program_id' => Program::factory(),
            'contest_id' => Contest::factory(),
            'definitive_list_id' => DefinitiveList::factory(),
            'status' => LotteryRunStatus::Completed->value,
            'lottery_method' => 'hash_seeded_order',
            'seed' => fake()->uuid(),
            'algorithm' => 'sha256(seed:participant)',
            'participants_count' => 1,
            'drawn_count' => 1,
            'started_by' => User::factory(),
            'started_at' => now(),
            'completed_at' => now(),
        ];
    }
}
