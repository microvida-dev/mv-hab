<?php

namespace Database\Factories;

use App\Models\ContextualFaq;
use App\Models\ContextualFaqCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContextualFaq>
 */
class ContextualFaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'contextual_faq_category_id' => ContextualFaqCategory::factory(),
            'context_key' => 'application',
            'question' => fake()->sentence(),
            'answer' => fake()->paragraph(),
            'keywords' => ['candidatura', 'apoio'],
            'sort_order' => 0,
            'is_active' => true,
            'published_at' => now(),
        ];
    }
}
