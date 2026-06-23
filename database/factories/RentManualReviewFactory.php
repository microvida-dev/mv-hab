<?php

namespace Database\Factories;

use App\Enums\RentManualReviewStatus;
use App\Models\RentCalculation;
use App\Models\RentManualReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RentManualReview> */
class RentManualReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rent_calculation_id' => RentCalculation::factory(),
            'requested_by' => User::factory(),
            'status' => RentManualReviewStatus::Pending->value,
            'original_rent' => 300,
            'proposed_rent' => 280,
            'reason' => 'Justificação administrativa fictícia.',
            'requested_at' => now(),
        ];
    }
}
