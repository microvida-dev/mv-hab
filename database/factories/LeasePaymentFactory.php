<?php

namespace Database\Factories;

use App\Enums\LeasePaymentStatus;
use App\Models\Contract;
use App\Models\LeasePayment;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeasePayment> */
class LeasePaymentFactory extends Factory
{
    protected $model = LeasePayment::class;

    public function definition(): array
    {
        return [
            'tenant_financial_account_id' => TenantFinancialAccount::factory(),
            'lease_contract_id' => Contract::factory(),
            'user_id' => User::factory(),
            'status' => LeasePaymentStatus::Confirmed->value,
            'payment_number' => 'PAY-TEST-'.fake()->unique()->numerify('######'),
            'amount' => 300,
            'allocated_amount' => 300,
            'unallocated_amount' => 0,
            'currency' => 'EUR',
            'payment_date' => today(),
            'value_date' => today(),
            'received_at' => now(),
            'confirmed_at' => now(),
            'method' => 'manual',
            'source' => 'testing',
            'external_reference' => 'EXT-TEST-'.fake()->unique()->numerify('######'),
            'payer_name' => 'Arrendatário Fictício',
        ];
    }
}
