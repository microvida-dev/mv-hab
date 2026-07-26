<?php

namespace Tests\Feature\Security;

use App\Models\Application;
use App\Models\Contract;
use App\Models\HousingUnit;
use App\Models\InspectionChecklistTemplate;
use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceIntervention;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSupplier;
use App\Models\Municipality;
use App\Models\Program;
use App\Models\PropertyInspection;
use App\Models\User;
use App\Services\Municipalities\OperationalMunicipalContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OperationalMunicipalContextServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_relations_follow_housing_municipality_and_system_rules(): void
    {
        $service = app(
            OperationalMunicipalContextService::class,
        );

        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();

        $housingUnit = HousingUnit::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);

        $categoryA = MaintenanceCategory::factory()->create([
            'municipality_id' => $municipalityA->id,
            'is_system' => false,
        ]);

        $categoryB = MaintenanceCategory::factory()->create([
            'municipality_id' => $municipalityB->id,
            'is_system' => false,
        ]);

        $systemCategory = MaintenanceCategory::factory()
            ->system()
            ->create();

        $invalidSystemCategory = MaintenanceCategory::factory()
            ->create([
                'municipality_id' => $municipalityA->id,
                'is_system' => true,
            ]);

        $this->assertSame(
            $categoryA->id,
            $service->categoryForHousingUnit(
                $categoryA->id,
                $housingUnit,
            )?->id,
        );

        $this->assertSame(
            $systemCategory->id,
            $service->categoryForHousingUnit(
                $systemCategory->id,
                $housingUnit,
            )?->id,
        );

        $this->assertValidationError(
            fn () => $service->categoryForHousingUnit(
                $categoryB->id,
                $housingUnit,
            ),
            'maintenance_category_id',
        );

        $this->assertValidationError(
            fn () => $service->categoryForHousingUnit(
                $invalidSystemCategory->id,
                $housingUnit,
            ),
            'maintenance_category_id',
        );

        $supplierA = MaintenanceSupplier::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);

        $supplierB = MaintenanceSupplier::factory()->create([
            'municipality_id' => $municipalityB->id,
        ]);

        $supplierWithoutMunicipality = MaintenanceSupplier::factory()
            ->create([
                'municipality_id' => null,
            ]);

        $this->assertSame(
            $supplierA->id,
            $service->supplierForHousingUnit(
                $supplierA->id,
                $housingUnit,
            )?->id,
        );

        $this->assertValidationError(
            fn () => $service->supplierForHousingUnit(
                $supplierB->id,
                $housingUnit,
            ),
            'maintenance_supplier_id',
        );

        $this->assertValidationError(
            fn () => $service->supplierForHousingUnit(
                $supplierWithoutMunicipality->id,
                $housingUnit,
            ),
            'maintenance_supplier_id',
        );

        $templateA = InspectionChecklistTemplate::factory()
            ->create([
                'municipality_id' => $municipalityA->id,
                'is_system' => false,
            ]);

        $templateB = InspectionChecklistTemplate::factory()
            ->create([
                'municipality_id' => $municipalityB->id,
                'is_system' => false,
            ]);

        $systemTemplate = InspectionChecklistTemplate::factory()
            ->system()
            ->create();

        $invalidSystemTemplate = InspectionChecklistTemplate::factory()
            ->create([
                'municipality_id' => $municipalityA->id,
                'is_system' => true,
            ]);

        $this->assertSame(
            $templateA->id,
            $service->templateForHousingUnit(
                $templateA->id,
                $housingUnit,
            )?->id,
        );

        $this->assertSame(
            $systemTemplate->id,
            $service->templateForHousingUnit(
                $systemTemplate->id,
                $housingUnit,
            )?->id,
        );

        $this->assertValidationError(
            fn () => $service->templateForHousingUnit(
                $templateB->id,
                $housingUnit,
            ),
            'inspection_checklist_template_id',
        );

        $this->assertValidationError(
            fn () => $service->templateForHousingUnit(
                $invalidSystemTemplate->id,
                $housingUnit,
            ),
            'inspection_checklist_template_id',
        );
    }

    public function test_contract_application_and_users_must_match_housing_municipality(): void
    {
        $service = app(
            OperationalMunicipalContextService::class,
        );

        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();

        $housingUnitA = HousingUnit::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);

        $housingUnitB = HousingUnit::factory()->create([
            'municipality_id' => $municipalityB->id,
        ]);

        $contractA = Contract::factory()->create([
            'housing_unit_id' => $housingUnitA->id,
        ]);

        $contractB = Contract::factory()->create([
            'housing_unit_id' => $housingUnitB->id,
        ]);

        $this->assertSame(
            $contractA->id,
            $service->contractForHousingUnit(
                $contractA->id,
                $housingUnitA,
            )?->id,
        );

        $this->assertValidationError(
            fn () => $service->contractForHousingUnit(
                $contractB->id,
                $housingUnitA,
            ),
            'lease_contract_id',
        );

        $programA = Program::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);

        $programB = Program::factory()->create([
            'municipality_id' => $municipalityB->id,
        ]);

        $applicationA = Application::factory()->create([
            'program_id' => $programA->id,
        ]);

        $applicationB = Application::factory()->create([
            'program_id' => $programB->id,
        ]);

        $this->assertSame(
            $applicationA->id,
            $service->applicationForHousingUnit(
                $applicationA->id,
                $housingUnitA,
            )?->id,
        );

        $this->assertValidationError(
            fn () => $service->applicationForHousingUnit(
                $applicationB->id,
                $housingUnitA,
            ),
            'application_id',
        );

        $userA = User::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);

        $userB = User::factory()->create([
            'municipality_id' => $municipalityB->id,
        ]);

        $platformActor = User::factory()->create([
            'municipality_id' => null,
        ]);

        $this->assertSame(
            $userA->id,
            $service->municipalUserForHousingUnit(
                $userA->id,
                $housingUnitA,
                'assigned_user_id',
            )?->id,
        );

        $this->assertValidationError(
            fn () => $service->municipalUserForHousingUnit(
                $userB->id,
                $housingUnitA,
                'assigned_user_id',
            ),
            'assigned_user_id',
        );

        $this->assertSame(
            $userA->id,
            $service->inspectorIdForHousingUnit(
                $userA,
                $housingUnitA,
                null,
            ),
        );

        $this->assertNull(
            $service->inspectorIdForHousingUnit(
                $platformActor,
                $housingUnitA,
                null,
            ),
        );

        $this->assertSame(
            $userA->id,
            $service->inspectorIdForHousingUnit(
                $platformActor,
                $housingUnitA,
                $userA->id,
            ),
        );

        $this->assertValidationError(
            fn () => $service->inspectorIdForHousingUnit(
                $platformActor,
                $housingUnitA,
                $userB->id,
            ),
            'inspector_user_id',
        );
    }

    public function test_operational_records_resolve_the_authoritative_housing_unit(): void
    {
        $service = app(
            OperationalMunicipalContextService::class,
        );

        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();

        $housingUnitA = HousingUnit::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);

        $housingUnitB = HousingUnit::factory()->create([
            'municipality_id' => $municipalityB->id,
        ]);

        $actorA = User::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);

        $actorB = User::factory()->create([
            'municipality_id' => $municipalityB->id,
        ]);

        $requestA = MaintenanceRequest::factory()->create([
            'housing_unit_id' => $housingUnitA->id,
        ]);

        $assignmentA = MaintenanceAssignment::factory()->create([
            'maintenance_request_id' => $requestA->id,
        ]);

        $interventionA = MaintenanceIntervention::factory()->create([
            'maintenance_request_id' => $requestA->id,
            'housing_unit_id' => $housingUnitA->id,
        ]);

        $costA = MaintenanceCost::factory()->create([
            'maintenance_request_id' => $requestA->id,
            'housing_unit_id' => $housingUnitA->id,
        ]);

        $inspectionA = PropertyInspection::factory()->create([
            'housing_unit_id' => $housingUnitA->id,
        ]);

        $this->assertSame(
            $housingUnitA->id,
            $service->housingUnitForActor(
                $actorA,
                $housingUnitA->id,
            )->id,
        );

        $this->assertSame(
            $housingUnitA->id,
            $service->maintenanceRequestHousingUnit(
                $actorA,
                $requestA,
            )->id,
        );

        $this->assertSame(
            $housingUnitA->id,
            $service->maintenanceAssignmentHousingUnit(
                $actorA,
                $assignmentA,
            )->id,
        );

        $this->assertSame(
            $housingUnitA->id,
            $service->maintenanceInterventionHousingUnit(
                $actorA,
                $interventionA,
            )->id,
        );

        $this->assertSame(
            $housingUnitA->id,
            $service->maintenanceCostHousingUnit(
                $actorA,
                $costA,
            )->id,
        );

        $this->assertSame(
            $housingUnitA->id,
            $service->propertyInspectionHousingUnit(
                $actorA,
                $inspectionA,
            )->id,
        );

        $this->assertValidationError(
            fn () => $service->housingUnitForActor(
                $actorB,
                $housingUnitA->id,
            ),
            'housing_unit_id',
        );

        $this->assertValidationError(
            fn () => $service->maintenanceRequestHousingUnit(
                $actorB,
                $requestA,
            ),
            'maintenance_request_id',
        );

        $this->assertValidationError(
            fn () => $service->maintenanceAssignmentHousingUnit(
                $actorB,
                $assignmentA,
            ),
            'maintenance_assignment_id',
        );

        $this->assertValidationError(
            fn () => $service->maintenanceInterventionHousingUnit(
                $actorB,
                $interventionA,
            ),
            'maintenance_intervention_id',
        );

        $this->assertValidationError(
            fn () => $service->maintenanceCostHousingUnit(
                $actorB,
                $costA,
            ),
            'maintenance_cost_id',
        );

        $this->assertValidationError(
            fn () => $service->propertyInspectionHousingUnit(
                $actorB,
                $inspectionA,
            ),
            'property_inspection_id',
        );
    }

    public function test_interventions_must_belong_to_the_selected_request(): void
    {
        $service = app(
            OperationalMunicipalContextService::class,
        );

        $municipality = Municipality::factory()->create();

        $housingUnit = HousingUnit::factory()->create([
            'municipality_id' => $municipality->id,
        ]);

        $requestA = MaintenanceRequest::factory()->create([
            'housing_unit_id' => $housingUnit->id,
        ]);

        $requestB = MaintenanceRequest::factory()->create([
            'housing_unit_id' => $housingUnit->id,
        ]);

        $interventionA = MaintenanceIntervention::factory()->create([
            'maintenance_request_id' => $requestA->id,
            'housing_unit_id' => $housingUnit->id,
        ]);

        $this->assertSame(
            $interventionA->id,
            $service->interventionForRequest(
                $interventionA->id,
                $requestA,
            )?->id,
        );

        $this->assertValidationError(
            fn () => $service->interventionForRequest(
                $interventionA->id,
                $requestB,
            ),
            'maintenance_intervention_id',
        );

        $this->assertValidationError(
            fn () => $service->housingUnitForActor(
                User::factory()->create([
                    'municipality_id' => $municipality->id,
                ]),
                null,
            ),
            'housing_unit_id',
        );
    }

    /**
     * @param  callable(): mixed  $callback
     */
    private function assertValidationError(
        callable $callback,
        string $field,
    ): void {
        try {
            $callback();

            $this->fail(
                "Era esperada uma ValidationException para {$field}.",
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                $field,
                $exception->errors(),
            );
        }
    }
}
