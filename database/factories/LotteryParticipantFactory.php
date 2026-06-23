<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\DefinitiveListEntry;
use App\Models\LotteryParticipant;
use App\Models\LotteryRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LotteryParticipant> */
class LotteryParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lottery_run_id' => LotteryRun::factory(),
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'definitive_list_entry_id' => DefinitiveListEntry::factory(),
            'participant_number' => 'LP-'.fake()->unique()->numerify('######'),
            'rank_position' => 1,
            'weight' => 1,
            'is_eligible' => true,
        ];
    }
}
