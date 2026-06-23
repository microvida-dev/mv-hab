<?php

namespace Database\Factories;

use App\Enums\ArrearStatus;
use App\Models\Arrear;
use App\Models\Contract;
use App\Models\RentInstallment;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Arrear> */
class ArrearFactory extends Factory
{
    protected $model = Arrear::class;

    public function definition(): array
    {
        $overdueSince = today()->subDays(18);

        return [
            'tenant_financial_account_id' => TenantFinancialAccount::factory(),
            'lease_contract_id' => Contract::factory(),
            'rent_installment_id' => RentInstallment::factory(),
            'user_id' => User::factory(),
            'status' => ArrearStatus::Open->value,
            'original_amount' => 300,
            'outstanding_amount' => 300,
            'overdue_since' => $overdueSince,
            'days_overdue' => $overdueSince->diffInDays(today()),
            'detected_at' => now(),
            'notes' => 'Mora fictícia para testes integrados.',
        ];
    }
}
