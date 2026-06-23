<?php

namespace Database\Factories;

use App\Enums\RentInstallmentStatus;
use App\Models\Contract;
use App\Models\RentInstallment;
use App\Models\RentSchedule;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RentInstallment> */
class RentInstallmentFactory extends Factory
{
    protected $model = RentInstallment::class;

    public function definition(): array
    {
        $dueDate = today()->addMonth()->day(8);

        return [
            'tenant_financial_account_id' => TenantFinancialAccount::factory(),
            'rent_schedule_id' => RentSchedule::factory(),
            'lease_contract_id' => Contract::factory(),
            'user_id' => User::factory(),
            'status' => RentInstallmentStatus::Issued->value,
            'reference' => 'RENT-TEST-'.fake()->unique()->numerify('######'),
            'period_year' => (int) $dueDate->format('Y'),
            'period_month' => (int) $dueDate->format('m'),
            'issue_date' => $dueDate->copy()->startOfMonth(),
            'due_date' => $dueDate,
            'original_amount' => 300,
            'amount_due' => 300,
            'amount_paid' => 0,
            'amount_outstanding' => 300,
            'amount_waived' => 0,
            'currency' => 'EUR',
            'issued_at' => now(),
        ];
    }
}
