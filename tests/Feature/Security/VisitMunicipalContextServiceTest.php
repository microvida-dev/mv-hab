<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Application;
use App\Models\Contest;
use App\Models\ContestHousingUnit;
use App\Models\HousingUnit;
use App\Models\HousingVisit;
use App\Models\Municipality;
use App\Models\PlatformOperatorAssignment;
use App\Models\Program;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use App\Services\Municipalities\VisitMunicipalContextService;
use App\Services\Visits\VisitAvailabilityService;
use App\Services\Visits\VisitBookingService;
use App\Services\Visits\VisitReschedulingService;
use App\Services\Visits\VisitSlotGenerationService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class VisitMunicipalContextServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_availability_context_requires_coherent_origins_staff_and_actor_scope(): void
    {
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();
        $actorA = User::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);
        $staffA = User::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);
        $staffB = User::factory()->create([
            'municipality_id' => $municipalityB->id,
        ]);
        $contestA = $this->contest($municipalityA);
        $housingUnitA = HousingUnit::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);
        $housingUnitB = HousingUnit::factory()->create([
            'municipality_id' => $municipalityB->id,
        ]);
        $service = app(VisitMunicipalContextService::class);

        $this->assertSame(
            $municipalityA->id,
            $service->municipalityForAvailabilityData(
                ['contest_id' => $contestA->id],
                $actorA,
            ),
        );
        $this->assertSame(
            $municipalityA->id,
            $service->municipalityForAvailabilityData(
                ['housing_unit_id' => $housingUnitA->id],
                $actorA,
            ),
        );
        $this->assertSame(
            $municipalityA->id,
            $service->municipalityForAvailabilityData([
                'contest_id' => $contestA->id,
                'housing_unit_id' => $housingUnitA->id,
                'staff_user_id' => $staffA->id,
            ], $actorA),
        );

        $this->assertValidationError(
            fn (): int => $service
                ->municipalityForAvailabilityData([
                    'contest_id' => $contestA->id,
                    'housing_unit_id' => $housingUnitB->id,
                ], $actorA),
            'availability',
        );
        $this->assertValidationError(
            fn (): int => $service
                ->municipalityForAvailabilityData([
                    'contest_id' => $contestA->id,
                    'staff_user_id' => $staffB->id,
                ], $actorA),
            'staff_user_id',
        );
        $this->assertValidationError(
            fn (): int => $service
                ->municipalityForAvailabilityData(
                    ['contest_id' => $contestA->id],
                    $staffB,
                ),
            'municipality',
        );
        $this->assertValidationError(
            fn (): int => $service
                ->municipalityForAvailabilityData(
                    ['contest_id' => $contestA->id],
                    User::factory()->withoutMunicipality()->create(),
                ),
            'municipality',
        );

        $globalOperator = User::factory()
            ->withoutMunicipality()
            ->create(['status' => 'active']);
        PlatformOperatorAssignment::factory()
            ->for($globalOperator)
            ->create();
        $this->assertSame(
            $municipalityA->id,
            $service->municipalityForAvailabilityData(
                ['contest_id' => $contestA->id],
                $globalOperator,
            ),
        );
    }

    public function test_slot_application_and_visit_context_reject_incoherent_relations(): void
    {
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();
        $candidate = $this->candidate();
        $contextA = $this->bookingContext(
            $municipalityA,
            $candidate,
        );
        $contextB = $this->bookingContext(
            $municipalityB,
            $candidate,
        );
        $service = app(VisitMunicipalContextService::class);

        $this->assertSame(
            $municipalityA->id,
            $service->validateSlot($contextA['slot']),
        );
        $this->assertSame(
            $municipalityA->id,
            $service->bookingContext(
                $contextA['slot'],
                $contextA['application'],
                $contextA['contest']->id,
                $contextA['housing_unit']->id,
            )['municipality_id'],
        );

        $incoherentApplication = Application::factory()->create([
            'user_id' => $candidate->id,
            'program_id' => $contextA['program']->id,
            'contest_id' => $contextB['contest']->id,
        ]);
        $this->assertValidationError(
            fn (): array => $service->bookingContext(
                $contextA['slot'],
                $incoherentApplication,
                $contextA['contest']->id,
                $contextA['housing_unit']->id,
            ),
            'application_id',
        );

        $foreignSlot = VisitSlot::factory()->create([
            'visit_availability_id' => $contextA['availability']->id,
            'contest_id' => $contextB['contest']->id,
            'housing_unit_id' => $contextA['housing_unit']->id,
        ]);
        $foreignSlot->forceFill([
            'municipality_id' => $municipalityA->id,
        ])->saveQuietly();
        $this->assertValidationError(
            fn (): int => $service->validateSlot($foreignSlot),
            'contest_id',
        );

        $visit = $this->visitFromContext($contextA, $candidate);
        $this->assertSame(
            $municipalityA->id,
            $service->validateVisit($visit),
        );

        $visit->forceFill([
            'visit_slot_id' => $contextB['slot']->id,
        ])->saveQuietly();
        $this->assertValidationError(
            fn (): int => $service->validateVisit(
                $visit->refresh(),
            ),
            'visit',
        );

        $visit->forceFill([
            'municipality_id' => null,
        ])->saveQuietly();
        $this->assertValidationError(
            fn (): int => $service->validateVisit(
                $visit->refresh(),
            ),
            'visit',
        );
    }

    public function test_availability_and_slot_services_fill_canonical_municipality_and_prevent_transfer(): void
    {
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();
        $actorA = User::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);
        $contestA = $this->contest($municipalityA);
        $contestB = $this->contest($municipalityB);
        $availabilityService = app(
            VisitAvailabilityService::class,
        );
        $availability = $availabilityService->store([
            'municipality_id' => $municipalityB->id,
            'contest_id' => $contestA->id,
            'title' => 'Disponibilidade municipal A',
            'starts_at' => now()->addDays(5)->setTime(9, 0),
            'ends_at' => now()->addDays(5)->setTime(10, 0),
            'slot_duration_minutes' => 30,
            'capacity_per_slot' => 1,
            'buffer_minutes' => 0,
            'is_active' => true,
        ], $actorA);

        $this->assertSame(
            $municipalityA->id,
            $availability->municipality_id,
        );

        $slots = app(VisitSlotGenerationService::class)
            ->generate($availability, $actorA);

        $this->assertCount(2, $slots);
        foreach ($slots as $slot) {
            $this->assertSame(
                $municipalityA->id,
                $slot->municipality_id,
            );
            $this->assertSame(
                $contestA->id,
                $slot->contest_id,
            );
        }

        $globalOperator = User::factory()
            ->withoutMunicipality()
            ->create(['status' => 'active']);
        PlatformOperatorAssignment::factory()
            ->for($globalOperator)
            ->create();
        $this->assertValidationError(
            fn (): VisitAvailability => $availabilityService
                ->update($availability, [
                    'contest_id' => $contestB->id,
                ], $globalOperator),
            'availability',
        );
        $this->assertSame(
            $municipalityA->id,
            $availability->refresh()->municipality_id,
        );
        $this->assertSame(
            $contestA->id,
            $availability->contest_id,
        );
    }

    public function test_booking_populates_municipality_and_fails_before_slot_mutation(): void
    {
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();
        $candidate = $this->candidate();
        $contextA = $this->bookingContext(
            $municipalityA,
            $candidate,
        );
        $contextB = $this->bookingContext(
            $municipalityB,
            $candidate,
        );
        $booking = app(VisitBookingService::class);

        $visit = $booking->book($candidate, [
            'visit_slot_id' => $contextA['slot']->id,
            'application_id' => $contextA['application']->id,
            'contest_id' => $contextA['contest']->id,
            'housing_unit_id' => $contextA['housing_unit']->id,
        ]);

        $this->assertNull($candidate->municipality_id);
        $this->assertSame(
            $municipalityA->id,
            $visit->municipality_id,
        );
        $this->assertSame(
            1,
            $contextA['slot']->refresh()->booked_count,
        );

        $beforeForeignCount = (int) $contextB['slot']
            ->refresh()
            ->booked_count;
        $this->assertValidationError(
            fn (): HousingVisit => $booking->book($candidate, [
                'visit_slot_id' => $contextB['slot']->id,
                'application_id' => $contextA['application']->id,
                'contest_id' => $contextA['contest']->id,
                'housing_unit_id' => $contextA['housing_unit']->id,
            ]),
            'contest_id',
        );
        $this->assertSame(
            $beforeForeignCount,
            (int) $contextB['slot']->refresh()->booked_count,
        );

        $orphanSlot = $contextB['slot'];
        $orphanSlot->forceFill([
            'municipality_id' => null,
        ])->saveQuietly();
        $this->assertValidationError(
            fn (): HousingVisit => $booking->book($candidate, [
                'visit_slot_id' => $orphanSlot->id,
                'contest_id' => $contextB['contest']->id,
                'housing_unit_id' => $contextB['housing_unit']->id,
            ]),
            'visit_slot_id',
        );
        $this->assertSame(
            $beforeForeignCount,
            (int) $orphanSlot->refresh()->booked_count,
        );
    }

    public function test_cross_municipality_rescheduling_is_atomic(): void
    {
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();
        $candidate = $this->candidate();
        $contextA = $this->bookingContext(
            $municipalityA,
            $candidate,
        );
        $contextB = $this->bookingContext(
            $municipalityB,
            $candidate,
        );
        $booking = app(VisitBookingService::class);
        $visit = $booking->book($candidate, [
            'visit_slot_id' => $contextA['slot']->id,
            'application_id' => $contextA['application']->id,
            'contest_id' => $contextA['contest']->id,
            'housing_unit_id' => $contextA['housing_unit']->id,
        ]);
        $oldSlotCount = (int) $contextA['slot']
            ->refresh()
            ->booked_count;
        $newSlotCount = (int) $contextB['slot']
            ->refresh()
            ->booked_count;
        $historyCount = DB::table(
            'housing_visit_status_histories',
        )->count();
        $auditCount = DB::table('audit_logs')->count();
        $taskCount = DB::table('work_tasks')->count();
        $notificationCount = DB::table(
            'official_notifications',
        )->count();

        $this->assertValidationError(
            fn (): HousingVisit => app(
                VisitReschedulingService::class,
            )->reschedule(
                $visit,
                $contextB['slot'],
                $candidate,
                'Tentativa entre Municípios.',
            ),
            'new_visit_slot_id',
        );

        $this->assertSame(
            $contextA['slot']->id,
            $visit->refresh()->visit_slot_id,
        );
        $this->assertSame(
            $oldSlotCount,
            (int) $contextA['slot']->refresh()->booked_count,
        );
        $this->assertSame(
            $newSlotCount,
            (int) $contextB['slot']->refresh()->booked_count,
        );
        $this->assertSame(
            $historyCount,
            DB::table('housing_visit_status_histories')->count(),
        );
        $this->assertSame(
            $auditCount,
            DB::table('audit_logs')->count(),
        );
        $this->assertSame(
            $taskCount,
            DB::table('work_tasks')->count(),
        );
        $this->assertSame(
            $notificationCount,
            DB::table('official_notifications')->count(),
        );
    }

    public function test_rescheduling_within_same_municipality_succeeds(): void
    {
        $municipality = Municipality::factory()->create();
        $candidate = $this->candidate();
        $context = $this->bookingContext(
            $municipality,
            $candidate,
        );
        $secondSlot = VisitSlot::factory()->create([
            'visit_availability_id' => $context['availability']->id,
            'contest_id' => $context['contest']->id,
            'housing_unit_id' => $context['housing_unit']->id,
            'starts_at' => now()->addDays(12)->setTime(15, 0),
            'ends_at' => now()->addDays(12)->setTime(15, 30),
        ]);
        $booking = app(VisitBookingService::class);
        $visit = $booking->book($candidate, [
            'visit_slot_id' => $context['slot']->id,
            'application_id' => $context['application']->id,
            'contest_id' => $context['contest']->id,
            'housing_unit_id' => $context['housing_unit']->id,
        ]);

        $rescheduled = app(
            VisitReschedulingService::class,
        )->reschedule(
            $visit,
            $secondSlot,
            $candidate,
            'Novo horário no mesmo Município.',
        );

        $this->assertSame(
            $secondSlot->id,
            $rescheduled->visit_slot_id,
        );
        $this->assertSame(
            0,
            (int) $context['slot']->refresh()->booked_count,
        );
        $this->assertSame(
            1,
            (int) $secondSlot->refresh()->booked_count,
        );
    }

    private function candidate(): User
    {
        $candidate = User::factory()
            ->withoutMunicipality()
            ->create();
        $candidate->assignRole('candidate');

        return $candidate;
    }

    private function contest(Municipality $municipality): Contest
    {
        $program = Program::factory()->published()->create([
            'municipality_id' => $municipality->id,
        ]);

        return Contest::factory()
            ->for($program)
            ->open()
            ->create();
    }

    /**
     * @return array{
     *     program: Program,
     *     contest: Contest,
     *     housing_unit: HousingUnit,
     *     availability: VisitAvailability,
     *     slot: VisitSlot,
     *     application: Application
     * }
     */
    private function bookingContext(
        Municipality $municipality,
        User $candidate,
    ): array {
        $program = Program::factory()->published()->create([
            'municipality_id' => $municipality->id,
        ]);
        $contest = Contest::factory()
            ->for($program)
            ->open()
            ->create();
        $housingUnit = HousingUnit::factory()
            ->publiclyVisible()
            ->create([
                'municipality_id' => $municipality->id,
            ]);
        ContestHousingUnit::factory()->create([
            'program_id' => $program->id,
            'contest_id' => $contest->id,
            'housing_unit_id' => $housingUnit->id,
        ]);
        $availability = VisitAvailability::factory()->create([
            'contest_id' => $contest->id,
            'housing_unit_id' => $housingUnit->id,
            'staff_user_id' => null,
        ]);
        $slot = VisitSlot::factory()->create([
            'visit_availability_id' => $availability->id,
            'contest_id' => $contest->id,
            'housing_unit_id' => $housingUnit->id,
            'staff_user_id' => null,
            'starts_at' => now()->addDays(10)->setTime(10, 0),
            'ends_at' => now()->addDays(10)->setTime(10, 30),
        ]);
        $application = Application::factory()->create([
            'user_id' => $candidate->id,
            'program_id' => $program->id,
            'contest_id' => $contest->id,
        ]);

        return [
            'program' => $program,
            'contest' => $contest,
            'housing_unit' => $housingUnit,
            'availability' => $availability,
            'slot' => $slot,
            'application' => $application,
        ];
    }

    /**
     * @param  array{
     *     contest: Contest,
     *     housing_unit: HousingUnit,
     *     slot: VisitSlot,
     *     application: Application
     * }  $context
     */
    private function visitFromContext(
        array $context,
        User $candidate,
    ): HousingVisit {
        return HousingVisit::factory()->create([
            'visit_slot_id' => $context['slot']->id,
            'application_id' => $context['application']->id,
            'contest_id' => $context['contest']->id,
            'housing_unit_id' => $context['housing_unit']->id,
            'candidate_user_id' => $candidate->id,
        ]);
    }

    private function assertValidationError(
        callable $callback,
        string $field,
    ): void {
        try {
            $callback();
            self::fail(
                'Era esperada uma ValidationException para '.$field.'.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                $field,
                $exception->errors(),
            );
        }
    }
}
