<?php

namespace Tests\Feature;

use App\Enums\ChargeType;
use App\Enums\ContractStatus;
use App\Enums\InspectionStatus;
use App\Enums\InspectionType;
use App\Enums\MaintenanceRequestStatus;
use App\Enums\MaintenanceSource;
use App\Enums\MaintenanceUrgency;
use App\Enums\TenantInvoiceStatus;
use App\Enums\TenantPaymentStatus;
use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\LeaseContractDocument;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceRequest;
use App\Models\PropertyInspection;
use App\Models\TenantFinancialAccount;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Models\User;
use App\Services\Finance\RentScheduleService;
use App\Services\Finance\TenantFinancialAccountService;
use App\Services\TenantPortal\TenantPortalAccessService;
use App\Support\AuditEvents;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QA26ContractsRentTenantPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_qa26_tenant_portal_enforces_ownership_for_contracts_invoices_payments_maintenance_and_inspections(): void
    {
        $context = $this->tenantContext();
        $otherContext = $this->tenantContext(['contract_number' => 'CTR-QA26-OTHER']);

        $invoice = TenantInvoice::factory()->create([
            'tenant_financial_account_id' => $context['account']->id,
            'lease_contract_id' => $context['contract']->id,
            'user_id' => $context['tenant']->id,
            'housing_unit_id' => $context['contract']->housing_unit_id,
            'invoice_number' => 'TINV-QA26-001',
        ]);
        $payment = TenantPayment::factory()->create([
            'tenant_invoice_id' => $invoice->id,
            'tenant_financial_account_id' => $context['account']->id,
            'lease_contract_id' => $context['contract']->id,
            'user_id' => $context['tenant']->id,
            'payment_number' => 'TPAY-QA26-001',
        ]);
        $maintenanceRequest = MaintenanceRequest::factory()->create([
            'housing_unit_id' => $context['contract']->housing_unit_id,
            'lease_contract_id' => $context['contract']->id,
            'user_id' => $context['tenant']->id,
            'status' => MaintenanceRequestStatus::New,
            'source' => MaintenanceSource::Tenant,
        ]);
        $inspection = PropertyInspection::factory()->create([
            'housing_unit_id' => $context['contract']->housing_unit_id,
            'lease_contract_id' => $context['contract']->id,
            'inspection_type' => InspectionType::Periodic,
            'status' => InspectionStatus::Scheduled,
            'tenant_visible' => true,
        ]);

        $this->actingAs($context['tenant'])
            ->get(route('tenant.contracts.show', $context['contract']))
            ->assertOk()
            ->assertSee($context['contract']->contract_number);
        $this->actingAs($context['tenant'])->get(route('tenant.invoices.show', $invoice))->assertOk();
        $this->actingAs($context['tenant'])->get(route('tenant.payments.show', $payment))->assertOk();
        $this->actingAs($context['tenant'])->get(route('tenant.maintenance.show', $maintenanceRequest))->assertOk();
        $this->actingAs($context['tenant'])->get(route('tenant.inspections.show', $inspection))->assertOk();

        $this->actingAs($otherContext['tenant'])
            ->get(route('tenant.contracts.show', $context['contract']))
            ->assertForbidden();
        $this->actingAs($otherContext['tenant'])->get(route('tenant.invoices.show', $invoice))->assertForbidden();
        $this->actingAs($otherContext['tenant'])->get(route('tenant.payments.show', $payment))->assertForbidden();
        $this->actingAs($otherContext['tenant'])->get(route('tenant.maintenance.show', $maintenanceRequest))->assertForbidden();
        $this->actingAs($otherContext['tenant'])->get(route('tenant.inspections.show', $inspection))->assertForbidden();
    }

    public function test_qa26_contract_documents_are_private_authorized_and_audited_on_download(): void
    {
        Storage::fake('local');
        $context = $this->tenantContext();
        $document = LeaseContractDocument::factory()->create([
            'lease_contract_id' => $context['contract']->id,
            'title' => 'Contrato QA26 privado',
            'storage_disk' => 'local',
            'storage_path' => 'contracts/qa26/contract.html',
            'html_content' => '<html><body>Contrato QA26 privado</body></html>',
        ]);
        Storage::disk('local')->put($document->storage_path, $document->html_content);

        $otherTenant = $this->tenantContext(['contract_number' => 'CTR-QA26-DOC-OTHER'])['tenant'];

        $this->actingAs($otherTenant)
            ->get(route('candidate.contracts.documents.download', $document))
            ->assertForbidden();

        $this->actingAs($context['tenant'])
            ->get(route('candidate.contracts.documents.download', $document))
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'event' => AuditEvents::ACCESS,
            'module' => 'contracts',
            'action' => 'lease_contract_document_download',
        ]);
        $this->assertSame(1, AuditLog::query()
            ->where('module', 'contracts')
            ->where('action', 'lease_contract_document_download')
            ->count());
    }

    public function test_qa26_rent_billing_rejects_negative_values_and_keeps_period_idempotent(): void
    {
        $context = $this->tenantContext();

        $this->actingAs($context['manager'])
            ->from(route('backoffice.tenant-operations.invoices.index'))
            ->post(route('backoffice.tenant-operations.invoices.store'), [
                'lease_contract_id' => $context['contract']->id,
                'period_year' => 2026,
                'period_month' => 7,
                'charge_type' => ChargeType::Rent->value,
                'amount' => -1,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(8)->toDateString(),
            ])
            ->assertRedirect(route('backoffice.tenant-operations.invoices.index'))
            ->assertSessionHasErrors('amount');

        $payload = [
            'lease_contract_id' => $context['contract']->id,
            'period_year' => 2026,
            'period_month' => 7,
            'charge_type' => ChargeType::Rent->value,
            'amount' => 310,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(8)->toDateString(),
        ];

        $this->actingAs($context['manager'])
            ->post(route('backoffice.tenant-operations.invoices.store'), $payload)
            ->assertRedirect();
        $this->actingAs($context['manager'])
            ->post(route('backoffice.tenant-operations.invoices.store'), $payload)
            ->assertRedirect();

        $invoice = TenantInvoice::query()->where('period_year', 2026)->where('period_month', 7)->firstOrFail();
        $this->assertSame(1, TenantInvoice::query()->where('period_year', 2026)->where('period_month', 7)->count());
        $this->assertSame(TenantInvoiceStatus::Issued, $invoice->status);

        $this->actingAs($context['manager'])
            ->from(route('backoffice.tenant-operations.payments.index'))
            ->post(route('backoffice.tenant-operations.payments.store'), [
                'tenant_invoice_id' => $invoice->id,
                'amount' => 0,
                'payment_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('backoffice.tenant-operations.payments.index'))
            ->assertSessionHasErrors('amount');

        $this->actingAs($context['manager'])
            ->post(route('backoffice.tenant-operations.payments.store'), [
                'tenant_invoice_id' => $invoice->id,
                'amount' => 310,
                'payment_date' => now()->toDateString(),
                'confirm_now' => true,
            ])
            ->assertRedirect();

        $this->assertSame(TenantPaymentStatus::Confirmed, TenantPayment::query()->firstOrFail()->status);
        $this->assertSame(TenantInvoiceStatus::Paid, $invoice->refresh()->status);
        $this->assertSame('0.00', $invoice->amount_outstanding);
    }

    public function test_qa26_rent_schedule_generation_preserves_previous_schedule_and_rejects_negative_rent(): void
    {
        $context = $this->tenantContext();

        $this->actingAs($context['manager'])
            ->from(route('backoffice.finance.schedules.index'))
            ->post(route('backoffice.finance.schedules.generate', $context['contract']), [
                'starts_on' => '2026-01-01',
                'ends_on' => '2026-03-01',
                'monthly_rent' => -50,
                'payment_day' => 8,
            ])
            ->assertRedirect(route('backoffice.finance.schedules.index'))
            ->assertSessionHasErrors('monthly_rent');

        app(RentScheduleService::class)->generateForContract($context['contract'], $context['manager'], [
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-03-01',
            'monthly_rent' => 300,
            'payment_day' => 8,
        ]);
        app(RentScheduleService::class)->generateForContract($context['contract'], $context['manager'], [
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-06-01',
            'monthly_rent' => 325,
            'payment_day' => 8,
            'schedule_type' => 'rent_review',
        ]);

        $this->assertDatabaseHas('rent_schedules', [
            'lease_contract_id' => $context['contract']->id,
            'monthly_rent' => 300,
            'status' => 'closed',
        ]);
        $this->assertDatabaseHas('rent_schedules', [
            'lease_contract_id' => $context['contract']->id,
            'monthly_rent' => 325,
            'status' => 'active',
        ]);
        $this->assertSame(6, $context['contract']->rentInstallments()->count());
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'finance',
            'action' => 'rent_schedule_generate',
        ]);
    }

    public function test_qa26_maintenance_workflow_rejects_premature_close_and_preserves_history(): void
    {
        $context = $this->tenantContext();
        $category = MaintenanceCategory::factory()->create(['default_urgency' => MaintenanceUrgency::Normal]);

        $this->actingAs($context['tenant'])
            ->post(route('tenant.maintenance.store'), [
                'maintenance_category_id' => $category->id,
                'urgency' => MaintenanceUrgency::Normal->value,
                'title' => 'Pedido QA26 manutenção',
                'description' => 'Descrição suficientemente longa para validação do pedido.',
                'location_in_property' => 'Cozinha',
            ])
            ->assertRedirect();

        $request = MaintenanceRequest::query()->firstOrFail();

        $this->actingAs($context['manager'])
            ->from(route('backoffice.maintenance.requests.show', $request))
            ->post(route('backoffice.maintenance.requests.close', $request), [
                'closure_notes' => 'Fecho prematuro.',
            ])
            ->assertRedirect(route('backoffice.maintenance.requests.show', $request))
            ->assertSessionHasErrors('status');

        $this->assertSame(MaintenanceRequestStatus::New, $request->refresh()->status);

        $this->actingAs($context['manager'])
            ->post(route('backoffice.maintenance.requests.review', $request), [
                'urgency' => MaintenanceUrgency::Normal->value,
                'technical_priority' => MaintenanceUrgency::Normal->value,
                'maintenance_category_id' => $category->id,
                'review_notes' => 'Revisto em QA26.',
            ])
            ->assertRedirect();
        $this->actingAs($context['manager'])
            ->post(route('backoffice.maintenance.requests.start', $request))
            ->assertRedirect();
        $this->actingAs($context['manager'])
            ->post(route('backoffice.maintenance.requests.resolve', $request), [
                'resolution_summary' => 'Resolvido em QA26.',
            ])
            ->assertRedirect();
        $this->actingAs($context['manager'])
            ->post(route('backoffice.maintenance.requests.close', $request), [
                'closure_notes' => 'Fechado em QA26.',
            ])
            ->assertRedirect();

        $this->assertSame(MaintenanceRequestStatus::Closed, $request->refresh()->status);
        $this->assertSame(5, $request->statusHistories()->count());
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'maintenance_requests',
            'action' => 'maintenance_status_changed',
        ]);
    }

    public function test_qa26_inspections_are_hidden_until_visible_and_private_attachments_do_not_leak(): void
    {
        $context = $this->tenantContext();
        $otherContext = $this->tenantContext(['contract_number' => 'CTR-QA26-INSPECTION-OTHER']);
        $inspection = PropertyInspection::factory()->create([
            'housing_unit_id' => $context['contract']->housing_unit_id,
            'lease_contract_id' => $context['contract']->id,
            'inspection_type' => InspectionType::Periodic,
            'status' => InspectionStatus::Scheduled,
            'tenant_visible' => false,
            'summary' => 'Resumo QA26 vistoria',
        ]);
        $inspection->attachments()->create([
            'uploaded_by' => $context['manager']->id,
            'attachment_type' => 'photo',
            'original_filename' => 'vistoria-privada-qa26.jpg',
            'storage_disk' => 'local',
            'storage_path' => 'inspections/private/vistoria-privada-qa26.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'visible_to_tenant' => false,
        ]);

        $this->actingAs($context['tenant'])
            ->get(route('tenant.inspections.show', $inspection))
            ->assertForbidden();

        $inspection->forceFill(['tenant_visible' => true])->save();

        $this->actingAs($otherContext['tenant'])
            ->get(route('tenant.inspections.show', $inspection))
            ->assertForbidden();

        $this->actingAs($context['tenant'])
            ->get(route('tenant.inspections.show', $inspection))
            ->assertOk()
            ->assertSee('Resumo QA26 vistoria')
            ->assertDontSee('vistoria-privada-qa26.jpg')
            ->assertDontSee('inspections/private/vistoria-privada-qa26.jpg');
    }

    /**
     * @param  array<string, mixed>  $contractOverrides
     * @return array{tenant:User, manager:User, contract:Contract, account:TenantFinancialAccount}
     */
    private function tenantContext(array $contractOverrides = []): array
    {
        $this->seed(SystemAccessSeeder::class);

        $tenant = User::factory()->create();
        $tenant->assignRole('candidate');

        $manager = User::factory()->create();
        $manager->assignRole('financial_manager');
        $manager->assignRole('maintenance_manager');

        $contract = Contract::factory()->create(array_merge([
            'user_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'tenant_email' => $tenant->email,
            'contract_number' => 'CTR-QA26-'.fake()->unique()->numerify('####'),
            'status' => ContractStatus::Active,
            'monthly_rent' => 300,
            'deposit_amount' => 300,
            'payment_day' => 8,
            'start_date' => now()->startOfMonth()->toDateString(),
            'activated_at' => now(),
            'activated_by' => $manager->id,
        ], $contractOverrides));

        app(TenantPortalAccessService::class)->ensureForUser($tenant, $manager);
        $account = app(TenantFinancialAccountService::class)->ensureForContract($contract, $manager);

        return compact('tenant', 'manager', 'contract', 'account');
    }
}
