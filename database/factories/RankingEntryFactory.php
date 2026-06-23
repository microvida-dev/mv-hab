<?php

namespace Database\Factories;

use App\Enums\RankingEntryStatus;
use App\Models\Application;
use App\Models\ApplicationScore;
use App\Models\RankingEntry;
use App\Models\RankingSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RankingEntry>
 */
class RankingEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ranking_snapshot_id' => RankingSnapshot::factory(),
            'application_score_id' => ApplicationScore::factory(),
            'application_id' => Application::factory()->submitted(),
            'rank_position' => 1,
            'previous_rank_position' => null,
            'total_score' => 10,
            'tie_breaker_values' => null,
            'is_tied' => false,
            'status' => RankingEntryStatus::Ranked->value,
        ];
    }
}
