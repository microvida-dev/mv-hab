<?php

namespace Database\Factories;

use App\Enums\RankingSnapshotStatus;
use App\Models\Program;
use App\Models\RankingSnapshot;
use App\Models\ScoringRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RankingSnapshot>
 */
class RankingSnapshotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'scoring_run_id' => ScoringRun::factory(),
            'program_id' => Program::factory(),
            'contest_id' => null,
            'snapshot_number' => 1,
            'status' => RankingSnapshotStatus::Internal->value,
            'generated_by' => User::factory(),
            'generated_at' => now(),
            'published_at' => null,
            'notes' => 'Snapshot fictício para teste.',
        ];
    }
}
