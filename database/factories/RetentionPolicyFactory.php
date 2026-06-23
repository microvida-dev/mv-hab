<?php

namespace Database\Factories;

use App\Enums\RetentionAction;
use App\Models\RetentionPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RetentionPolicy> */
class RetentionPolicyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(2),
            'name' => 'Política de retenção demo',
            'status' => 'active',
            'entity_type' => 'App\\Models\\DataSubjectRequest',
            'retention_period_months' => 60,
            'retention_action' => RetentionAction::ReviewManually->value,
            'requires_manual_approval' => true,
        ];
    }
}
