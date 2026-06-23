<?php

namespace Database\Factories;

use App\Enums\DepositStatus;
use App\Models\Allocation;
use App\Models\Application;
use App\Models\Contract;
use App\Models\ContractDeposit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContractDeposit> */
class ContractDepositFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lease_contract_id' => Contract::factory(),
            'application_id' => Application::factory()->submitted(),
            'allocation_id' => Allocation::factory(),
            'user_id' => User::factory(),
            'status' => DepositStatus::Pending->value,
            'amount' => 300,
            'currency' => 'EUR',
            'calculation_basis' => 'Base fictícia de teste.',
        ];
    }
}
