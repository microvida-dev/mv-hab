<?php

namespace Database\Factories;

use App\Enums\ChargeType;
use App\Enums\TenantInvoiceStatus;
use App\Models\TenantFinancialAccount;
use App\Models\TenantInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TenantInvoice> */
class TenantInvoiceFactory extends Factory
{
    protected $model = TenantInvoice::class;

    public function definition(): array
    {
        $account = TenantFinancialAccount::factory()->create();
        $amount = fake()->randomFloat(2, 125, 650);

        return [
            'tenant_financial_account_id' => $account->id,
            'lease_contract_id' => $account->lease_contract_id,
            'user_id' => $account->user_id,
            'housing_unit_id' => $account->housing_unit_id,
            'invoice_number' => 'TINV-'.fake()->unique()->numerify('######'),
            'status' => TenantInvoiceStatus::Issued->value,
            'charge_type' => ChargeType::Rent->value,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'issue_date' => now()->startOfMonth()->toDateString(),
            'due_date' => now()->startOfMonth()->addDays(7)->toDateString(),
            'original_amount' => $amount,
            'amount_due' => $amount,
            'amount_paid' => 0,
            'amount_outstanding' => $amount,
            'currency' => 'EUR',
            'issued_at' => now(),
        ];
    }
}
