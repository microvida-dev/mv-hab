<?php

namespace Database\Factories;

use App\Enums\RentScheduleStatus;
use App\Models\Contract;
use App\Models\RentSchedule;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RentSchedule> */
class RentScheduleFactory extends Factory
{
    protected $model = RentSchedule::class;

    public function definition(): array
    {
        return [
            'tenant_financial_account_id' => TenantFinancialAccount::factory(),
            'lease_contract_id' => Contract::factory(),
            'user_id' => User::factory(),
            'status' => RentScheduleStatus::Active->value,
            'schedule_type' => 'initial',
            'starts_on' => today()->startOfMonth(),
            'ends_on' => today()->addYear()->endOfMonth(),
            'monthly_rent' => 300,
            'payment_day' => 8,
            'issue_day' => 1,
            'due_grace_days' => 0,
            'generated_installments_count' => 1,
        ];
    }
}
