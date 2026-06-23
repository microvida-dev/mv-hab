<?php

namespace Database\Factories;

use App\Enums\ApplicationReviewStatus;
use App\Enums\ApplicationReviewType;
use App\Models\AdministrativeProcess;
use App\Models\ApplicationReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationReview>
 */
class ApplicationReviewFactory extends Factory
{
    public function definition(): array
    {
        $process = AdministrativeProcess::factory()->create();

        return [
            'administrative_process_id' => $process->id,
            'application_id' => $process->application_id,
            'review_type' => ApplicationReviewType::Preliminary->value,
            'status' => ApplicationReviewStatus::InProgress->value,
            'reviewed_by' => User::factory(),
            'started_at' => now(),
            'summary' => 'Análise administrativa fictícia.',
        ];
    }
}
