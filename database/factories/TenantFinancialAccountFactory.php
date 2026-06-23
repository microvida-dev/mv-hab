<?php

namespace Database\Factories;

use App\Enums\FinancialAccountStatus;
use App\Models\Allocation;
use App\Models\Application;
use App\Models\Contract;
use App\Models\HousingUnit;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TenantFinancialAccount> */
class TenantFinancialAccountFactory extends Factory
{
    protected $model = TenantFinancialAccount::class;

    public function definition(): array
    {
        return [
            'lease_contract_id' => Contract::factory(),
            'application_id' => Application::factory()->submitted(),
            'allocation_id' => Allocation::factory(),
            'user_id' => User::factory(),
            'housing_unit_id' => HousingUnit::factory(),
            'account_number' => 'ACC-TEST-'.fake()->unique()->numerify('######'),
            'status' => FinancialAccountStatus::Active->value,
            'currency' => 'EUR',
            'opened_at' => now(),
            'current_balance' => 0,
            'total_issued' => 0,
            'total_paid' => 0,
            'total_overdue' => 0,
            'total_waived' => 0,
        ];
    }
}
