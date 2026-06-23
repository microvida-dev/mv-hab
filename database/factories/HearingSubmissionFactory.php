<?php

namespace Database\Factories;

use App\Enums\HearingSubmissionStatus;
use App\Models\Application;
use App\Models\Hearing;
use App\Models\HearingSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HearingSubmission> */
class HearingSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hearing_id' => Hearing::factory(),
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'submission_text' => 'Pronúncia fictícia do candidato.',
            'submitted_at' => now(),
            'status' => HearingSubmissionStatus::Submitted->value,
        ];
    }
}
