<?php

namespace Database\Factories;

use App\Models\TenantChargeRun;
use App\Models\TenantChargeRunItem;
use App\Models\TenantInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TenantChargeRunItem> */
class TenantChargeRunItemFactory extends Factory
{
    protected $model = TenantChargeRunItem::class;

    public function definition(): array
    {
        $invoice = TenantInvoice::factory()->create();

        return [
            'tenant_charge_run_id' => TenantChargeRun::factory(),
            'tenant_invoice_id' => $invoice->id,
            'tenant_financial_account_id' => $invoice->tenant_financial_account_id,
            'lease_contract_id' => $invoice->lease_contract_id,
            'user_id' => $invoice->user_id,
            'housing_unit_id' => $invoice->housing_unit_id,
            'status' => 'generated',
            'amount' => $invoice->amount_due,
            'message' => 'Fatura gerada em execução operacional.',
        ];
    }
}
