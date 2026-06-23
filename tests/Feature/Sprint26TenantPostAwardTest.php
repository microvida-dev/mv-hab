<?php

namespace Tests\Feature;

use App\Enums\ChargeType;
use App\Enums\ContractStatus;
use App\Enums\InspectionStatus;
use App\Enums\InspectionType;
use App\Enums\MaintenanceUrgency;
use App\Enums\TenantInvoiceStatus;
use App\Enums\TenantPaymentStatus;
use App\Models\Contract;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceRequest;
use App\Models\PropertyInspection;
use App\Models\TenantChargeRun;
use App\Models\TenantCommunication;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint26TenantPostAwardTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_area_requires_active_post_award_contract_and_blocks_unrelated_candidate(): void
    {
        $context = $this->tenantContext();

        $this->get(route('tenant.dashboard'))->assertRedirect();

        $this->actingAs($context['candidate'])
            ->get(route('tenant.dashboard'))
            ->assertOk()
            ->assertSee('Área do inquilino')
            ->assertSee($context['contract']->contract_number);

        $otherCandidate = User::factory()->create();
        $otherCandidate->assignRole('candidate');

        $this->actingAs($otherCandidate)
            ->get(route('tenant.dashboard'))
            ->assertForbidden();
    }

    public function test_backoffice_issues_invoice_registers_payment_and_tenant_only_sees_own_records(): void
    {
        $context = $this->tenantContext();

        $this->actingAs($context['manager'])
            ->post(route('backoffice.tenant-operations.invoices.store'), [
                'lease_contract_id' => $context['contract']->id,
                'period_year' => now()->year,
                'period_month' => now()->month,
                'charge_type' => ChargeType::Rent->value,
                'amount' => 300,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(8)->toDateString(),
            ])
            ->assertRedirect();

        $invoice = TenantInvoice::query()->firstOrFail();
        $this->assertSame(TenantInvoiceStatus::Issued, $invoice->status);

        $this->actingAs($context['candidate'])
            ->get(route('tenant.invoices.show', $invoice))
            ->assertOk()
            ->assertSee($invoice->invoice_number);

        $otherCandidate = User::factory()->create();
        $otherCandidate->assignRole('candidate');

        $this->actingAs($otherCandidate)
            ->get(route('tenant.invoices.show', $invoice))
            ->assertForbidden();

        $this->actingAs($context['manager'])
            ->post(route('backoffice.tenant-operations.payments.store'), [
                'tenant_invoice_id' => $invoice->id,
                'amount' => 300,
                'payment_date' => now()->toDateString(),
                'confirm_now' => true,
            ])
            ->assertRedirect();

        $payment = TenantPayment::query()->firstOrFail();
        $this->assertSame(TenantPaymentStatus::Confirmed, $payment->status);
        $this->assertSame(TenantInvoiceStatus::Paid, $invoice->refresh()->status);

        $this->actingAs($context['candidate'])
            ->get(route('tenant.payments.show', $payment))
            ->assertOk()
            ->assertSee($payment->payment_number);
    }

    public function test_operational_charge_run_generates_internal_invoices_without_bank_integration(): void
    {
        $context = $this->tenantContext();

        $this->actingAs($context['manager'])
            ->post(route('backoffice.tenant-operations.charge-runs.store'), [
                'period_year' => now()->addMonth()->year,
                'period_month' => now()->addMonth()->month,
                'charge_type' => ChargeType::Rent->value,
            ])
            ->assertRedirect();

        $run = TenantChargeRun::query()->firstOrFail();
        $this->assertSame(1, (int) $run->generated_count);
        $this->assertSame(1, TenantInvoice::query()->count());
    }

    public function test_tenant_communications_are_isolated_and_backoffice_can_reply(): void
    {
        $context = $this->tenantContext();
        $otherContract = Contract::factory()->create([
            'user_id' => User::factory()->create()->id,
            'status' => ContractStatus::Active,
        ]);

        $this->actingAs($context['candidate'])
            ->post(route('tenant.communications.store'), [
                'lease_contract_id' => $otherContract->id,
                'subject' => 'Pedido de esclarecimento',
                'body' => 'Solicito informação sobre o contrato ativo.',
            ])
            ->assertRedirect();

        $communication = TenantCommunication::query()->firstOrFail();
        $this->assertSame($context['candidate']->id, $communication->user_id);
        $this->assertNull($communication->lease_contract_id);

        $this->actingAs($context['manager'])
            ->post(route('backoffice.tenant-operations.communications.messages.store', $communication), [
                'body' => 'Mensagem registada pelos serviços municipais.',
                'visible_to_tenant' => true,
            ])
            ->assertRedirect();

        $this->assertSame(2, $communication->messages()->count());

        $otherCandidate = User::factory()->create();
        $otherCandidate->assignRole('candidate');

        $this->actingAs($otherCandidate)
            ->get(route('tenant.communications.show', $communication))
            ->assertForbidden();
    }

    public function test_tenant_maintenance_and_inspections_use_existing_private_modules(): void
    {
        $context = $this->tenantContext();
        $category = MaintenanceCategory::factory()->create(['default_urgency' => MaintenanceUrgency::Normal]);

        $this->actingAs($context['candidate'])
            ->post(route('tenant.maintenance.store'), [
                'maintenance_category_id' => $category->id,
                'urgency' => MaintenanceUrgency::Normal->value,
                'title' => 'Torneira com fuga',
                'description' => 'Existe uma fuga contínua na torneira da cozinha.',
                'location_in_property' => 'Cozinha',
            ])
            ->assertRedirect();

        $request = MaintenanceRequest::query()->firstOrFail();
        $this->assertSame($context['candidate']->id, $request->user_id);
        $this->assertSame($context['contract']->id, $request->lease_contract_id);

        $inspection = PropertyInspection::factory()->create([
            'housing_unit_id' => $context['contract']->housing_unit_id,
            'lease_contract_id' => $context['contract']->id,
            'inspection_type' => InspectionType::Periodic,
            'status' => InspectionStatus::Scheduled,
            'tenant_visible' => true,
            'scheduled_for' => now()->addDays(3),
        ]);

        $this->actingAs($context['candidate'])
            ->get(route('tenant.inspections.show', $inspection))
            ->assertOk()
            ->assertSee($inspection->inspection_number);

        $this->actingAs($context['manager'])
            ->get(route('backoffice.tenant-operations.dashboard'))
            ->assertOk()
            ->assertSee('Exploração pós-atribuição');
    }

    private function tenantContext(): array
    {
        $this->seed(SystemAccessSeeder::class);

        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $manager = User::factory()->create();
        $manager->assignRole('financial_manager');

        $contract = Contract::factory()->create([
            'user_id' => $candidate->id,
            'tenant_name' => $candidate->name,
            'tenant_email' => $candidate->email,
            'contract_number' => 'CTR-TENANT-'.fake()->unique()->numerify('####'),
            'status' => ContractStatus::Active,
            'monthly_rent' => 300,
            'deposit_amount' => 300,
            'payment_day' => 8,
            'start_date' => now()->startOfMonth()->toDateString(),
            'activated_at' => now(),
            'activated_by' => $manager->id,
        ]);

        return compact('candidate', 'manager', 'contract');
    }
}
