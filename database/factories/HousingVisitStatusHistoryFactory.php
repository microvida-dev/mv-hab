<?php

namespace Database\Factories;

use App\Enums\VisitStatus;
use App\Models\HousingVisit;
use App\Models\HousingVisitStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HousingVisitStatusHistory>
 */
class HousingVisitStatusHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'housing_visit_id' => HousingVisit::factory(),
            'from_status' => null,
            'to_status' => VisitStatus::PendingConfirmation->value,
            'changed_by' => User::factory(),
            'reason' => 'Histórico de demonstração',
            'changed_at' => now(),
        ];
    }
}
