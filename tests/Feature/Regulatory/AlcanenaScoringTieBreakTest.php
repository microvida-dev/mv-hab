<?php

namespace Tests\Feature\Regulatory;

use App\Models\Contest;
use App\Models\ScoringRule;
use App\Models\ScoringRuleSet;
use Database\Seeders\DemoAlcanenaAffordableRentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlcanenaScoringTieBreakTest extends TestCase
{
    use RefreshDatabase;

    public function test_scoring_matrix_and_tie_breakers_are_deterministic(): void
    {
        $this->seed(DemoAlcanenaAffordableRentSeeder::class);

        $contest = Contest::query()->where('code', DemoAlcanenaAffordableRentSeeder::CONTEST_CODE)->firstOrFail();
        $ruleSet = ScoringRuleSet::query()->where('contest_id', $contest->id)->firstOrFail();

        $this->assertSame(4, $ruleSet->criteria()->count());
        $this->assertSame(18, ScoringRule::query()->whereHas('criterion', fn ($query) => $query->where('scoring_rule_set_id', $ruleSet->id))->count());

        $this->assertSame(
            [
                'average_age_classification_points',
                'qualification_classification_points',
                'dependents_classification_points',
                'disability_classification_points',
            ],
            $ruleSet->tieBreakerRules()->orderBy('priority_order')->pluck('code')->all(),
        );
    }
}
