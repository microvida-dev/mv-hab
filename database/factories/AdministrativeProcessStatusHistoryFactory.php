<?php

namespace Database\Factories;

use App\Enums\AdministrativeProcessStatus;
use App\Models\AdministrativeProcess;
use App\Models\AdministrativeProcessStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdministrativeProcessStatusHistory>
 */
class AdministrativeProcessStatusHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'administrative_process_id' => AdministrativeProcess::factory(),
            'from_status' => null,
            'to_status' => AdministrativeProcessStatus::Received->value,
            'changed_by' => User::factory(),
            'reason' => 'Histórico fictício de teste.',
            'created_at' => now(),
        ];
    }
}
