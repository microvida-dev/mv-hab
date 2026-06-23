<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationStatusHistory>
 */
class ApplicationStatusHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'from_status' => ApplicationStatus::Draft->value,
            'to_status' => ApplicationStatus::Submitted->value,
            'changed_by' => User::factory(),
            'reason' => null,
        ];
    }
}
