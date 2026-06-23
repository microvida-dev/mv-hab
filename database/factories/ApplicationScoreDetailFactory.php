<?php

namespace Database\Factories;

use App\Enums\ScoreCriterionResult;
use App\Models\ApplicationScore;
use App\Models\ApplicationScoreDetail;
use App\Models\ScoringCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationScoreDetail>
 */
class ApplicationScoreDetailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_score_id' => ApplicationScore::factory(),
            'scoring_criterion_id' => ScoringCriterion::factory(),
            'scoring_rule_id' => null,
            'code' => 'criterion_'.fake()->unique()->numberBetween(1, 9999),
            'name' => 'Critério fictício',
            'category' => 'other',
            'result' => ScoreCriterionResult::Applied->value,
            'points_awarded' => 10,
            'max_points' => 10,
            'weight' => 1,
            'raw_value' => ['value' => true],
            'normalized_value' => null,
            'message' => 'Critério aplicado em teste.',
            'technical_message' => 'Mensagem técnica fictícia.',
            'requires_manual_review' => false,
        ];
    }
}
