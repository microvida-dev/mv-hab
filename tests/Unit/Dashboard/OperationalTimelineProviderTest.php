<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\DataSubjectRequestStatus;
use App\Enums\InspectionStatus;
use App\Enums\InternalAlertStatus;
use App\Enums\KeyHandoverStatus;
use App\Enums\MaintenanceInterventionStatus;
use App\Enums\MaintenanceRequestStatus;
use App\Enums\VisitStatus;
use App\Models\Contest;
use App\Models\DataSubjectRequest;
use App\Models\HousingVisit;
use App\Models\InternalAlert;
use App\Models\KeyHandoverAppointment;
use App\Models\MaintenanceIntervention;
use App\Models\MaintenanceRequest;
use App\Models\Municipality;
use App\Models\PlatformOperatorAssignment;
use App\Models\Program;
use App\Models\PropertyInspection;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use App\Services\Dashboard\Timeline\Providers\InspectionTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\InternalAlertTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\KeyHandoverTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\MaintenanceTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\RgpdTimelineProvider;
use App\Services\Dashboard\Timeline\Providers\VisitTimelineProvider;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OperationalTimelineProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_inspection_provider_exposes_only_today_schedule_from_user_municipality(): void
    {
        $municipality = Municipality::factory()->create();
        $otherMunicipality = Municipality::factory()->create();

        $user = $this->authorizedUser(
            $municipality->id,
        );

        $inspection = PropertyInspection::factory()->create([
            'status' => InspectionStatus::Scheduled,
            'scheduled_for' => today()->setTime(12, 0),
        ]);

        $inspection->housingUnit()->update([
            'municipality_id' => $municipality->id,
        ]);

        $foreignInspection = PropertyInspection::factory()->create([
            'status' => InspectionStatus::Scheduled,
            'scheduled_for' => today()->setTime(13, 0),
        ]);

        $foreignInspection->housingUnit()->update([
            'municipality_id' => $otherMunicipality->id,
        ]);

        $events = app(
            InspectionTimelineProvider::class,
        )->forUser($user);

        $this->assertCount(1, $events);
        $this->assertSame(
            TimelineType::Inspection,
            $events[0]->type,
        );
        $this->assertSame(
            InspectionStatus::Scheduled->value,
            $events[0]->metadata['status'],
        );
        $this->assertSame(
            $inspection->id,
            $events[0]->metadata['inspection_id'],
        );
        $this->assertNotSame(
            $foreignInspection->id,
            $events[0]->metadata['inspection_id'],
        );
    }

    public function test_inspection_provider_fails_closed_without_municipal_or_global_scope(): void
    {
        PropertyInspection::factory()->create([
            'status' => InspectionStatus::Scheduled,
            'scheduled_for' => today()->setTime(12, 0),
        ]);

        $events = app(
            InspectionTimelineProvider::class,
        )->forUser(
            $this->authorizedUser(
                withoutMunicipality: true,
            ),
        );

        $this->assertSame([], $events);
    }

    public function test_inspection_provider_requires_source_permission(): void
    {
        $municipality = Municipality::factory()->create();

        $inspection = PropertyInspection::factory()->create([
            'status' => InspectionStatus::Scheduled,
            'scheduled_for' => today()->setTime(12, 0),
        ]);

        $inspection->housingUnit()->update([
            'municipality_id' => $municipality->id,
        ]);

        $user = User::factory()->create([
            'status' => 'active',
            'municipality_id' => $municipality->id,
        ]);

        $events = app(
            InspectionTimelineProvider::class,
        )->forUser($user);

        $this->assertSame([], $events);
    }

    public function test_internal_alert_provider_exposes_active_deadline(): void
    {
        InternalAlert::factory()->create([
            'status' => InternalAlertStatus::Open,
            'due_at' => now()->addDay(),
        ]);

        $events = (new InternalAlertTimelineProvider)->forUser($this->authorizedUser());

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::InternalAlert, $events[0]->type);
        $this->assertSame(InternalAlertStatus::Open->value, $events[0]->metadata['status']);
    }

    public function test_key_handover_provider_uses_application_reference(): void
    {
        $appointment = KeyHandoverAppointment::factory()->create([
            'status' => KeyHandoverStatus::Scheduled,
            'scheduled_for' => now()->addWeek(),
        ]);

        $application = $appointment->application;

        $this->assertNotNull($application);

        $applicationNumber = $application->application_number;

        $this->assertIsString($applicationNumber);

        $events = (new KeyHandoverTimelineProvider)
            ->forUser($this->authorizedUser());

        $this->assertCount(1, $events);
        $this->assertSame(
            TimelineType::KeyHandover,
            $events[0]->type,
        );
        $this->assertSame(
            $applicationNumber,
            $events[0]->metadata['application_number'],
        );
        $this->assertArrayNotHasKey(
            'appointment_number',
            $events[0]->metadata,
        );
    }

    public function test_maintenance_provider_uses_request_reference_and_filters_municipality(): void
    {
        $municipality = Municipality::factory()->create();
        $otherMunicipality = Municipality::factory()->create();

        $user = $this->authorizedUser(
            $municipality->id,
        );

        $request = MaintenanceRequest::factory()->create([
            'status' => MaintenanceRequestStatus::Scheduled,
            'scheduled_for' => now()->addDays(2),
        ]);

        $request->housingUnit()->update([
            'municipality_id' => $municipality->id,
        ]);

        MaintenanceIntervention::factory()->create([
            'maintenance_request_id' => $request->id,
            'housing_unit_id' => $request->housing_unit_id,
            'status' => MaintenanceInterventionStatus::Scheduled,
            'scheduled_for' => now()->addDays(3),
        ]);

        $otherRequest = MaintenanceRequest::factory()->create([
            'status' => MaintenanceRequestStatus::Scheduled,
            'scheduled_for' => now()->addDays(4),
        ]);

        $otherRequest->housingUnit()->update([
            'municipality_id' => $otherMunicipality->id,
        ]);

        MaintenanceIntervention::factory()->create([
            'maintenance_request_id' => $otherRequest->id,
            'housing_unit_id' => $otherRequest->housing_unit_id,
            'status' => MaintenanceInterventionStatus::Scheduled,
            'scheduled_for' => now()->addDays(5),
        ]);

        $events = app(
            MaintenanceTimelineProvider::class,
        )->forUser($user);

        $intervention = collect($events)->firstWhere(
            'type',
            TimelineType::MaintenanceIntervention,
        );

        $this->assertCount(2, $events);
        $this->assertNotNull($intervention);
        $this->assertSame(
            $request->request_number,
            $intervention->metadata['request_number'],
        );
        $this->assertArrayNotHasKey(
            'intervention_number',
            $intervention->metadata,
        );

        $requestIds = collect($events)
            ->pluck('metadata.maintenance_request_id')
            ->filter()
            ->all();

        $this->assertContains(
            $request->id,
            $requestIds,
        );

        $this->assertNotContains(
            $otherRequest->id,
            $requestIds,
        );
    }

    public function test_maintenance_provider_fails_closed_without_municipal_or_global_scope(): void
    {
        MaintenanceRequest::factory()->create([
            'status' => MaintenanceRequestStatus::Scheduled,
            'scheduled_for' => now()->addDays(2),
        ]);

        $events = app(
            MaintenanceTimelineProvider::class,
        )->forUser(
            $this->authorizedUser(
                withoutMunicipality: true,
            ),
        );

        $this->assertSame([], $events);
    }

    public function test_rgpd_provider_uses_request_type_contract(): void
    {
        $request = DataSubjectRequest::factory()->create([
            'status' => DataSubjectRequestStatus::UnderReview,
            'due_at' => now()->addDays(10),
        ]);

        $events = (new RgpdTimelineProvider)->forUser($this->authorizedUser());

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::RgpdRequest, $events[0]->type);
        $this->assertSame($request->request_type->value, $events[0]->metadata['type']);
    }

    public function test_visit_provider_exposes_only_today_schedule_from_user_municipality(): void
    {
        $municipality = Municipality::factory()->create();
        $otherMunicipality = Municipality::factory()->create();
        $user = $this->authorizedUser($municipality->id);
        $visit = $this->visitForMunicipality(
            $municipality,
            today()->setTime(12, 0),
        );
        $foreignVisit = $this->visitForMunicipality(
            $otherMunicipality,
            today()->setTime(13, 0),
        );

        $events = app(VisitTimelineProvider::class)
            ->forUser($user);

        $this->assertCount(1, $events);
        $this->assertSame(TimelineType::Visit, $events[0]->type);
        $this->assertSame(VisitStatus::Confirmed->value, $events[0]->metadata['status']);
        $this->assertSame(
            $visit->id,
            $events[0]->metadata['visit_id'],
        );
        $this->assertNotSame(
            $foreignVisit->id,
            $events[0]->metadata['visit_id'],
        );
    }

    public function test_visit_provider_fails_closed_without_municipal_or_global_scope(): void
    {
        $municipality = Municipality::factory()->create();
        $this->visitForMunicipality(
            $municipality,
            today()->setTime(12, 0),
        );

        $events = app(VisitTimelineProvider::class)
            ->forUser($this->authorizedUser(
                withoutMunicipality: true,
            ));

        $this->assertSame([], $events);
    }

    public function test_visit_provider_allows_explicit_active_global_operator_scope(): void
    {
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();
        $visitA = $this->visitForMunicipality(
            $municipalityA,
            today()->setTime(12, 0),
        );
        $visitB = $this->visitForMunicipality(
            $municipalityB,
            today()->setTime(13, 0),
        );
        $operator = $this->authorizedUser(
            withoutMunicipality: true,
        );
        PlatformOperatorAssignment::factory()
            ->for($operator)
            ->create();

        $events = app(VisitTimelineProvider::class)
            ->forUser($operator);

        $this->assertEqualsCanonicalizing(
            [$visitA->id, $visitB->id],
            collect($events)
                ->pluck('metadata.visit_id')
                ->all(),
        );
    }

    public function test_visit_provider_requires_source_permission(): void
    {
        $municipality = Municipality::factory()->create();
        $this->visitForMunicipality(
            $municipality,
            today()->setTime(12, 0),
        );
        $user = User::factory()->create([
            'status' => 'active',
            'municipality_id' => $municipality->id,
        ]);

        $events = app(VisitTimelineProvider::class)
            ->forUser($user);

        $this->assertSame([], $events);
    }

    private function authorizedUser(
        ?int $municipalityId = null,
        bool $withoutMunicipality = false,
    ): User {
        $attributes = [
            'status' => 'active',
        ];

        if (
            $municipalityId !== null
            || $withoutMunicipality
        ) {
            $attributes['municipality_id']
                = $municipalityId;
        }

        $user = User::factory()->create(
            $attributes,
        );

        $user->assignRole('administrator');

        return $user;
    }

    private function visitForMunicipality(
        Municipality $municipality,
        \DateTimeInterface $scheduledAt,
    ): HousingVisit {
        $program = Program::factory()->create([
            'municipality_id' => $municipality->id,
        ]);
        $contest = Contest::factory()->create([
            'program_id' => $program->id,
        ]);
        $availability = VisitAvailability::factory()->create([
            'contest_id' => $contest->id,
        ]);
        $slot = VisitSlot::factory()->create([
            'visit_availability_id' => $availability->id,
            'contest_id' => $contest->id,
        ]);

        return HousingVisit::factory()->create([
            'visit_slot_id' => $slot->id,
            'contest_id' => $contest->id,
            'status' => VisitStatus::Confirmed,
            'scheduled_at' => $scheduledAt,
        ]);
    }
}
