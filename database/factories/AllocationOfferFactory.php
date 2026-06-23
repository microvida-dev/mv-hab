<?php

namespace Database\Factories;

use App\Enums\AllocationOfferStatus;
use App\Models\Allocation;
use App\Models\AllocationOffer;
use App\Models\Application;
use App\Models\ContestHousingUnit;
use App\Models\HousingUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AllocationOffer> */
class AllocationOfferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'allocation_id' => Allocation::factory(),
            'application_id' => Application::factory()->submitted(),
            'user_id' => User::factory(),
            'contest_housing_unit_id' => ContestHousingUnit::factory(),
            'housing_unit_id' => HousingUnit::factory(),
            'offer_number' => 'OAF-'.now()->format('Y').'-'.fake()->unique()->numerify('######'),
            'status' => AllocationOfferStatus::PendingResponse->value,
            'issued_by' => User::factory(),
            'issued_at' => now(),
            'response_deadline_at' => now()->addDays(10),
        ];
    }
}
