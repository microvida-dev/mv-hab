<?php

namespace Database\Factories;

use App\Enums\ComplaintReviewResult;
use App\Models\Complaint;
use App\Models\ComplaintReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ComplaintReview> */
class ComplaintReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'complaint_id' => Complaint::factory(),
            'reviewed_by' => User::factory(),
            'status' => 'completed',
            'result' => ComplaintReviewResult::Rejected->value,
            'summary' => 'Análise fictícia.',
            'started_at' => now(),
            'completed_at' => now(),
        ];
    }
}
