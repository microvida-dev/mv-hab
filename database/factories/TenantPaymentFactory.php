<?php

namespace Database\Factories;

use App\Enums\TenantPaymentStatus;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TenantPayment> */
class TenantPaymentFactory extends Factory
{
    protected $model = TenantPayment::class;

    public function definition(): array
    {
        $invoice = TenantInvoice::factory()->create();
        $amount = fake()->randomFloat(2, 25, (float) $invoice->amount_due);

        return [
            'tenant_invoice_id' => $invoice->id,
            'tenant_financial_account_id' => $invoice->tenant_financial_account_id,
            'lease_contract_id' => $invoice->lease_contract_id,
            'user_id' => $invoice->user_id,
            'payment_number' => 'TPAY-'.fake()->unique()->numerify('######'),
            'status' => TenantPaymentStatus::Registered->value,
            'amount' => $amount,
            'allocated_amount' => 0,
            'unallocated_amount' => $amount,
            'payment_date' => now()->toDateString(),
            'value_date' => now()->toDateString(),
            'registered_at' => now(),
            'method' => 'manual',
            'source' => 'backoffice',
        ];
    }
}
