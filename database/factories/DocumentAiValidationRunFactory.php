<?php

namespace Database\Factories;

use App\Enums\DocumentAiValidationStatus;
use App\Models\Application;
use App\Models\DocumentAiValidationRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentAiValidationRun>
 */
class DocumentAiValidationRunFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'status' => DocumentAiValidationStatus::Completed->value,
            'total_checks' => 0,
            'matches_count' => 0,
            'critical_count' => 0,
            'medium_count' => 0,
            'light_count' => 0,
            'inconclusive_count' => 0,
            'requires_manual_review' => false,
            'started_at' => now(),
            'completed_at' => now(),
            'failed_at' => null,
            'failure_reason' => null,
            'created_by' => User::factory(),
        ];
    }
}
