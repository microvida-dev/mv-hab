<?php

namespace Database\Factories;

use App\Enums\LotteryDrawStatus;
use App\Enums\LotteryDrawType;
use App\Models\AllocationRun;
use App\Models\Contest;
use App\Models\DefinitiveList;
use App\Models\LotteryDraw;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LotteryDraw> */
class LotteryDrawFactory extends Factory
{
    protected $model = LotteryDraw::class;

    public function definition(): array
    {
        return [
            'allocation_run_id' => AllocationRun::factory(),
            'program_id' => Program::factory(),
            'contest_id' => Contest::factory(),
            'definitive_list_id' => DefinitiveList::factory(),
            'status' => LotteryDrawStatus::Draft->value,
            'draw_type' => LotteryDrawType::General->value,
            'lottery_method' => 'hash_seeded_order',
            'seed' => fake()->uuid(),
            'seed_hash' => hash('sha256', fake()->uuid()),
            'algorithm' => 'sha256(seed:participant)',
            'scheduled_at' => now()->addWeek(),
            'location' => 'Sala municipal de testes',
        ];
    }
}
