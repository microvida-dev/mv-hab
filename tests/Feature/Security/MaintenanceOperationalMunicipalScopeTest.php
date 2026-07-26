<?php

namespace Tests\Feature\Security;

use App\Models\HousingUnit;
use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceIntervention;
use App\Models\MaintenanceRequest;
use App\Models\Municipality;
use App\Models\PlatformOperatorAssignment;
use App\Models\PropertyInspection;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceOperationalMunicipalScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_records_are_isolated_by_housing_unit_municipality(): void
    {
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();

        $actorA = User::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);

        $housingUnitA = HousingUnit::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);

        $housingUnitB = HousingUnit::factory()->create([
            'municipality_id' => $municipalityB->id,
        ]);

        $requestA = MaintenanceRequest::factory()->create([
            'housing_unit_id' => $housingUnitA->id,
        ]);

        $requestB = MaintenanceRequest::factory()->create([
            'housing_unit_id' => $housingUnitB->id,
        ]);

        $assignmentA = MaintenanceAssignment::factory()->create([
            'maintenance_request_id' => $requestA->id,
        ]);

        $assignmentB = MaintenanceAssignment::factory()->create([
            'maintenance_request_id' => $requestB->id,
        ]);

        $interventionA = MaintenanceIntervention::factory()->create([
            'maintenance_request_id' => $requestA->id,
            'housing_unit_id' => $housingUnitA->id,
        ]);

        $interventionB = MaintenanceIntervention::factory()->create([
            'maintenance_request_id' => $requestB->id,
            'housing_unit_id' => $housingUnitB->id,
        ]);

        $costA = MaintenanceCost::factory()->create([
            'maintenance_request_id' => $requestA->id,
            'housing_unit_id' => $housingUnitA->id,
        ]);

        $costB = MaintenanceCost::factory()->create([
            'maintenance_request_id' => $requestB->id,
            'housing_unit_id' => $housingUnitB->id,
        ]);

        $inspectionA = PropertyInspection::factory()->create([
            'housing_unit_id' => $housingUnitA->id,
        ]);

        $inspectionB = PropertyInspection::factory()->create([
            'housing_unit_id' => $housingUnitB->id,
        ]);

        $scope = app(MunicipalRecordScopeService::class);

        $this->assertTrue(
            $scope->ownsMaintenanceRequest($actorA, $requestA),
        );
        $this->assertFalse(
            $scope->ownsMaintenanceRequest($actorA, $requestB),
        );

        $this->assertTrue(
            $scope->ownsMaintenanceAssignment($actorA, $assignmentA),
        );
        $this->assertFalse(
            $scope->ownsMaintenanceAssignment($actorA, $assignmentB),
        );

        $this->assertTrue(
            $scope->ownsMaintenanceIntervention($actorA, $interventionA),
        );
        $this->assertFalse(
            $scope->ownsMaintenanceIntervention($actorA, $interventionB),
        );

        $this->assertTrue(
            $scope->ownsMaintenanceCost($actorA, $costA),
        );
        $this->assertFalse(
            $scope->ownsMaintenanceCost($actorA, $costB),
        );

        $this->assertTrue(
            $scope->ownsPropertyInspection($actorA, $inspectionA),
        );
        $this->assertFalse(
            $scope->ownsPropertyInspection($actorA, $inspectionB),
        );

        $this->assertSame(
            [$requestA->id],
            $scope->maintenanceRequests(
                MaintenanceRequest::query(),
                $actorA,
            )->pluck('id')->all(),
        );

        $this->assertSame(
            [$assignmentA->id],
            $scope->maintenanceAssignments(
                MaintenanceAssignment::query(),
                $actorA,
            )->pluck('id')->all(),
        );

        $this->assertSame(
            [$interventionA->id],
            $scope->maintenanceInterventions(
                MaintenanceIntervention::query(),
                $actorA,
            )->pluck('id')->all(),
        );

        $this->assertSame(
            [$costA->id],
            $scope->maintenanceCosts(
                MaintenanceCost::query(),
                $actorA,
            )->pluck('id')->all(),
        );

        $this->assertSame(
            [$inspectionA->id],
            $scope->propertyInspections(
                PropertyInspection::query(),
                $actorA,
            )->pluck('id')->all(),
        );
    }

    public function test_user_without_municipality_has_no_implicit_operational_scope(): void
    {
        $municipality = Municipality::factory()->create();

        $actor = User::factory()->create([
            'municipality_id' => null,
        ]);

        $housingUnit = HousingUnit::factory()->create([
            'municipality_id' => $municipality->id,
        ]);

        $request = MaintenanceRequest::factory()->create([
            'housing_unit_id' => $housingUnit->id,
        ]);

        $assignment = MaintenanceAssignment::factory()->create([
            'maintenance_request_id' => $request->id,
        ]);

        $intervention = MaintenanceIntervention::factory()->create([
            'maintenance_request_id' => $request->id,
            'housing_unit_id' => $housingUnit->id,
        ]);

        $cost = MaintenanceCost::factory()->create([
            'maintenance_request_id' => $request->id,
            'housing_unit_id' => $housingUnit->id,
        ]);

        $inspection = PropertyInspection::factory()->create([
            'housing_unit_id' => $housingUnit->id,
        ]);

        $scope = app(MunicipalRecordScopeService::class);

        $this->assertFalse(
            $scope->ownsMaintenanceRequest($actor, $request),
        );
        $this->assertFalse(
            $scope->ownsMaintenanceAssignment($actor, $assignment),
        );
        $this->assertFalse(
            $scope->ownsMaintenanceIntervention($actor, $intervention),
        );
        $this->assertFalse(
            $scope->ownsMaintenanceCost($actor, $cost),
        );
        $this->assertFalse(
            $scope->ownsPropertyInspection($actor, $inspection),
        );

        $this->assertSame(
            0,
            $scope->maintenanceRequests(
                MaintenanceRequest::query(),
                $actor,
            )->count(),
        );

        $this->assertSame(
            0,
            $scope->propertyInspections(
                PropertyInspection::query(),
                $actor,
            )->count(),
        );
    }

    public function test_explicit_platform_operator_scope_is_global_and_invalid_assignments_fail_closed(): void
    {
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();

        $housingUnitA = HousingUnit::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);

        $housingUnitB = HousingUnit::factory()->create([
            'municipality_id' => $municipalityB->id,
        ]);

        $requestA = MaintenanceRequest::factory()->create([
            'housing_unit_id' => $housingUnitA->id,
        ]);

        $requestB = MaintenanceRequest::factory()->create([
            'housing_unit_id' => $housingUnitB->id,
        ]);

        $assignmentA = MaintenanceAssignment::factory()->create([
            'maintenance_request_id' => $requestA->id,
        ]);

        $assignmentB = MaintenanceAssignment::factory()->create([
            'maintenance_request_id' => $requestB->id,
        ]);

        $interventionA = MaintenanceIntervention::factory()->create([
            'maintenance_request_id' => $requestA->id,
            'housing_unit_id' => $housingUnitA->id,
        ]);

        $interventionB = MaintenanceIntervention::factory()->create([
            'maintenance_request_id' => $requestB->id,
            'housing_unit_id' => $housingUnitB->id,
        ]);

        $costA = MaintenanceCost::factory()->create([
            'maintenance_request_id' => $requestA->id,
            'housing_unit_id' => $housingUnitA->id,
        ]);

        $costB = MaintenanceCost::factory()->create([
            'maintenance_request_id' => $requestB->id,
            'housing_unit_id' => $housingUnitB->id,
        ]);

        $inspectionA = PropertyInspection::factory()->create([
            'housing_unit_id' => $housingUnitA->id,
        ]);

        $inspectionB = PropertyInspection::factory()->create([
            'housing_unit_id' => $housingUnitB->id,
        ]);

        $activeOperator = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);

        PlatformOperatorAssignment::factory()
            ->for($activeOperator)
            ->create();

        $revokedOperator = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);

        PlatformOperatorAssignment::factory()
            ->revoked()
            ->for($revokedOperator)
            ->create();

        $inactiveOperator = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);

        PlatformOperatorAssignment::factory()
            ->for($inactiveOperator)
            ->create();

        $inactiveOperator->update([
            'status' => 'inactive',
        ]);

        $scope = app(MunicipalRecordScopeService::class);

        foreach ([$requestA, $requestB] as $request) {
            $this->assertTrue(
                $scope->ownsMaintenanceRequest(
                    $activeOperator,
                    $request,
                ),
            );
        }

        foreach ([$assignmentA, $assignmentB] as $assignment) {
            $this->assertTrue(
                $scope->ownsMaintenanceAssignment(
                    $activeOperator,
                    $assignment,
                ),
            );
        }

        foreach ([$interventionA, $interventionB] as $intervention) {
            $this->assertTrue(
                $scope->ownsMaintenanceIntervention(
                    $activeOperator,
                    $intervention,
                ),
            );
        }

        foreach ([$costA, $costB] as $cost) {
            $this->assertTrue(
                $scope->ownsMaintenanceCost(
                    $activeOperator,
                    $cost,
                ),
            );
        }

        foreach ([$inspectionA, $inspectionB] as $inspection) {
            $this->assertTrue(
                $scope->ownsPropertyInspection(
                    $activeOperator,
                    $inspection,
                ),
            );
        }

        $this->assertSame(
            2,
            $scope->maintenanceRequests(
                MaintenanceRequest::query(),
                $activeOperator,
            )->count(),
        );

        $this->assertSame(
            2,
            $scope->maintenanceAssignments(
                MaintenanceAssignment::query(),
                $activeOperator,
            )->count(),
        );

        $this->assertSame(
            2,
            $scope->maintenanceInterventions(
                MaintenanceIntervention::query(),
                $activeOperator,
            )->count(),
        );

        $this->assertSame(
            2,
            $scope->maintenanceCosts(
                MaintenanceCost::query(),
                $activeOperator,
            )->count(),
        );

        $this->assertSame(
            2,
            $scope->propertyInspections(
                PropertyInspection::query(),
                $activeOperator,
            )->count(),
        );

        foreach (
            [$revokedOperator, $inactiveOperator] as $invalidOperator
        ) {
            $this->assertFalse(
                $scope->ownsMaintenanceRequest(
                    $invalidOperator,
                    $requestA,
                ),
            );

            $this->assertFalse(
                $scope->ownsMaintenanceAssignment(
                    $invalidOperator,
                    $assignmentA,
                ),
            );

            $this->assertFalse(
                $scope->ownsMaintenanceIntervention(
                    $invalidOperator,
                    $interventionA,
                ),
            );

            $this->assertFalse(
                $scope->ownsMaintenanceCost(
                    $invalidOperator,
                    $costA,
                ),
            );

            $this->assertFalse(
                $scope->ownsPropertyInspection(
                    $invalidOperator,
                    $inspectionA,
                ),
            );

            $this->assertSame(
                0,
                $scope->maintenanceRequests(
                    MaintenanceRequest::query(),
                    $invalidOperator,
                )->count(),
            );

            $this->assertSame(
                0,
                $scope->maintenanceAssignments(
                    MaintenanceAssignment::query(),
                    $invalidOperator,
                )->count(),
            );

            $this->assertSame(
                0,
                $scope->maintenanceInterventions(
                    MaintenanceIntervention::query(),
                    $invalidOperator,
                )->count(),
            );

            $this->assertSame(
                0,
                $scope->maintenanceCosts(
                    MaintenanceCost::query(),
                    $invalidOperator,
                )->count(),
            );

            $this->assertSame(
                0,
                $scope->propertyInspections(
                    PropertyInspection::query(),
                    $invalidOperator,
                )->count(),
            );
        }
    }
}
