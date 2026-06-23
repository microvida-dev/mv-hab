<?php

namespace Tests\Feature;

use App\Enums\ContractStatus;
use App\Enums\InspectionCondition;
use App\Enums\InspectionReportStatus;
use App\Enums\InspectionStatus;
use App\Enums\InspectionType;
use App\Enums\MaintenanceAssignmentStatus;
use App\Enums\MaintenanceAssignmentType;
use App\Enums\MaintenanceCostStatus;
use App\Enums\MaintenanceCostType;
use App\Enums\MaintenanceInterventionStatus;
use App\Enums\MaintenanceRequestStatus;
use App\Enums\MaintenanceUrgency;
use App\Models\Contract;
use App\Models\InspectionChecklistTemplate;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceIntervention;
use App\Models\MaintenanceRequest;
use App\Models\PropertyHistoryEvent;
use App\Models\PropertyInspection;
use App\Models\PropertyInspectionReport;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Sprint15MaintenanceInspectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_creates_request_for_own_active_contract_and_cannot_mass_assign_admin_fields(): void
    {
        Storage::fake('local');
        $context = $this->maintenanceContext();

        $this->actingAs($context['candidate'])
            ->post(route('candidate.maintenance.requests.store'), [
                'maintenance_category_id' => $context['category']->id,
                'urgency' => MaintenanceUrgency::Normal->value,
                'technical_priority' => MaintenanceUrgency::Emergency->value,
                'status' => MaintenanceRequestStatus::Closed->value,
                'title' => 'Infiltração na cozinha',
                'description' => 'Existe uma infiltração visível junto ao lava-loiça.',
                'location_in_property' => 'Cozinha',
                'tenant_availability' => 'Dias úteis de manhã.',
                'attachments' => [UploadedFile::fake()->image('foto-cozinha.jpg')],
            ])
            ->assertRedirect();

        $request = MaintenanceRequest::query()->firstOrFail();

        $this->assertSame($context['candidate']->id, $request->user_id);
        $this->assertSame($context['contract']->id, $request->lease_contract_id);
        $this->assertSame($context['contract']->housing_unit_id, $request->housing_unit_id);
        $this->assertNotNull($request->request_number);
        $this->assertSame(MaintenanceRequestStatus::New, $request->status);
        $this->assertNull($request->technical_priority);
        $this->assertSame(1, $request->statusHistories()->count());
        $this->assertSame(1, $request->attachments()->count());
        Storage::disk('local')->assertExists($request->attachments()->firstOrFail()->storage_path);

        $otherCandidate = User::factory()->create();
        $otherCandidate->assignRole('candidate');

        $this->actingAs($otherCandidate)
            ->get(route('candidate.maintenance.requests.show', $request))
            ->assertForbidden();
    }

    public function test_backoffice_reviews_assigns_intervenes_costs_resolves_and_closes_request(): void
    {
        $context = $this->maintenanceContext();
        $request = MaintenanceRequest::factory()->create([
            'housing_unit_id' => $context['contract']->housing_unit_id,
            'lease_contract_id' => $context['contract']->id,
            'user_id' => $context['candidate']->id,
            'maintenance_category_id' => $context['category']->id,
            'status' => MaintenanceRequestStatus::New,
            'urgency' => MaintenanceUrgency::Normal,
        ]);

        $technician = User::factory()->create();
        $technician->assignRole('municipal_technician');

        $this->actingAs($context['manager'])
            ->post(route('backoffice.maintenance.requests.review', $request), [
                'urgency' => MaintenanceUrgency::Urgent->value,
                'technical_priority' => MaintenanceUrgency::Urgent->value,
                'maintenance_category_id' => $context['category']->id,
                'review_notes' => 'Validado para intervenção técnica.',
            ])
            ->assertRedirect();

        $this->assertSame(MaintenanceRequestStatus::UnderReview, $request->refresh()->status);

        $this->actingAs($context['manager'])
            ->post(route('backoffice.maintenance.assignments.store', $request), [
                'assignment_type' => MaintenanceAssignmentType::InternalTechnician->value,
                'assigned_user_id' => $technician->id,
                'assignment_notes' => 'Atribuição interna.',
            ])
            ->assertRedirect();

        $assignment = $request->assignments()->firstOrFail();
        $this->assertSame(MaintenanceAssignmentStatus::Assigned, $assignment->status);

        $this->actingAs($context['manager'])
            ->post(route('backoffice.maintenance.interventions.store', $request), [
                'scheduled_for' => now()->addDay()->toDateTimeString(),
                'performed_by_user_id' => $technician->id,
                'work_description' => 'Intervenção técnica planeada.',
            ])
            ->assertRedirect();

        $intervention = MaintenanceIntervention::query()->firstOrFail();
        $this->assertSame(MaintenanceInterventionStatus::Scheduled, $intervention->status);

        $this->actingAs($context['manager'])
            ->post(route('backoffice.maintenance.interventions.complete', $intervention), [
                'work_description' => 'Foi reparada a vedação afetada.',
                'result_summary' => 'Problema resolvido em visita técnica.',
                'materials_used' => 'Vedante técnico.',
            ])
            ->assertRedirect();

        $this->assertSame(MaintenanceInterventionStatus::Completed, $intervention->refresh()->status);

        $this->actingAs($context['manager'])
            ->post(route('backoffice.maintenance.costs.store', $request), [
                'maintenance_intervention_id' => $intervention->id,
                'cost_type' => MaintenanceCostType::Materials->value,
                'description' => 'Material de reparação.',
                'amount' => 35.50,
                'currency' => 'EUR',
            ])
            ->assertRedirect();

        $cost = MaintenanceCost::query()->firstOrFail();
        $this->assertSame(MaintenanceCostStatus::Estimated, $cost->status);

        $this->actingAs($context['manager'])
            ->post(route('backoffice.maintenance.costs.approve', $cost))
            ->assertRedirect();

        $this->assertSame(MaintenanceCostStatus::Approved, $cost->refresh()->status);

        $this->actingAs($context['manager'])
            ->post(route('backoffice.maintenance.requests.resolve', $request), [
                'resolution_summary' => 'Pedido resolvido após intervenção municipal.',
            ])
            ->assertRedirect();

        $this->actingAs($context['manager'])
            ->post(route('backoffice.maintenance.requests.close', $request), [
                'closure_notes' => 'Fecho administrativo.',
            ])
            ->assertRedirect();

        $this->assertSame(MaintenanceRequestStatus::Closed, $request->refresh()->status);
        $this->assertTrue(PropertyHistoryEvent::query()->where('housing_unit_id', $request->housing_unit_id)->exists());
    }

    public function test_inspection_checklist_report_and_tenant_visibility_flow(): void
    {
        Storage::fake('local');
        $context = $this->maintenanceContext();
        $template = InspectionChecklistTemplate::factory()->create(['inspection_type' => InspectionType::Periodic]);
        $template->items()->create(['code' => 'walls', 'label' => 'Paredes', 'sort_order' => 1]);

        $this->actingAs($context['manager'])
            ->post(route('backoffice.inspections.store'), [
                'housing_unit_id' => $context['contract']->housing_unit_id,
                'lease_contract_id' => $context['contract']->id,
                'inspection_checklist_template_id' => $template->id,
                'inspection_type' => InspectionType::Periodic->value,
                'scheduled_for' => now()->addDays(2)->toDateTimeString(),
            ])
            ->assertRedirect();

        $inspection = PropertyInspection::query()->firstOrFail();
        $this->assertSame(1, $inspection->items()->count());
        $this->assertSame(InspectionStatus::Scheduled, $inspection->status);

        $this->actingAs($context['manager'])
            ->post(route('backoffice.inspections.complete', $inspection), [
                'general_condition' => InspectionCondition::Acceptable->value,
                'summary' => 'Vistoria concluída sem anomalias críticas.',
                'recommendations' => 'Acompanhar em vistoria periódica futura.',
            ])
            ->assertRedirect();

        $this->actingAs($context['manager'])
            ->post(route('backoffice.inspections.validate', $inspection))
            ->assertRedirect();

        $this->actingAs($context['manager'])
            ->post(route('backoffice.inspections.reports.generate', $inspection))
            ->assertRedirect();

        $report = PropertyInspectionReport::query()->firstOrFail();
        $this->assertSame(InspectionReportStatus::Generated, $report->status);
        Storage::disk('local')->assertExists($report->storage_path);

        $this->actingAs($context['manager'])
            ->post(route('backoffice.inspections.reports.validate', $report))
            ->assertRedirect();

        $this->assertSame(InspectionReportStatus::Validated, $report->refresh()->status);

        $this->actingAs($context['candidate'])
            ->get(route('candidate.inspections.show', $inspection))
            ->assertOk()
            ->assertSee($inspection->inspection_number);

        $this->actingAs($context['candidate'])
            ->get(route('candidate.inspections.reports.download', $report))
            ->assertOk();
    }

    public function test_dashboard_and_backoffice_are_protected_from_candidate(): void
    {
        $context = $this->maintenanceContext();

        $this->actingAs($context['manager'])
            ->get(route('backoffice.maintenance.index'))
            ->assertOk();

        $this->actingAs($context['manager'])
            ->get(route('backoffice.maintenance.cost-reports.index'))
            ->assertOk();

        $this->actingAs($context['candidate'])
            ->get(route('backoffice.maintenance.index'))
            ->assertForbidden();
    }

    private function maintenanceContext(): array
    {
        $this->seed(SystemAccessSeeder::class);

        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        $manager = User::factory()->create();
        $manager->assignRole('maintenance_manager');

        $contract = Contract::factory()->create([
            'user_id' => $candidate->id,
            'tenant_name' => $candidate->name,
            'tenant_email' => $candidate->email,
            'contract_number' => 'CTR-MAINT-'.fake()->unique()->numerify('####'),
            'status' => ContractStatus::Active,
            'activated_at' => now(),
            'activated_by' => $manager->id,
            'start_date' => now()->subMonth()->toDateString(),
        ]);

        $category = MaintenanceCategory::factory()->create(['default_urgency' => MaintenanceUrgency::Normal]);

        return compact('candidate', 'manager', 'contract', 'category');
    }
}
