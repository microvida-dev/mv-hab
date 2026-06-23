<?php

namespace Database\Factories;

use App\Enums\ApplicationReviewResult;
use App\Models\ApplicationReview;
use App\Models\ApplicationReviewItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationReviewItem>
 */
class ApplicationReviewItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_review_id' => ApplicationReview::factory(),
            'code' => fake()->unique()->bothify('ITEM-###'),
            'name' => 'Ponto de análise fictício',
            'category' => 'manual',
            'result' => ApplicationReviewResult::Passed->value,
            'message' => 'Sem inconformidades nos dados fictícios.',
            'requires_correction' => false,
        ];
    }
}
