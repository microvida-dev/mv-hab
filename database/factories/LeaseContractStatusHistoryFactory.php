<?php

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\LeaseContractStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeaseContractStatusHistory> */
class LeaseContractStatusHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lease_contract_id' => Contract::factory(),
            'from_status' => null,
            'to_status' => ContractStatus::Preparation->value,
            'changed_by' => User::factory(),
            'reason' => 'Histórico fictício.',
            'created_at' => now(),
        ];
    }
}
