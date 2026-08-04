<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Enums\VisitSlotStatus;
use App\Enums\VisitStatus;
use App\Models\Contest;
use App\Models\HousingVisit;
use App\Models\Municipality;
use App\Models\PlatformOperatorAssignment;
use App\Models\Program;
use App\Models\User;
use App\Models\VisitAvailability;
use App\Models\VisitSlot;
use App\Services\BackofficeDashboard\VisitStatisticsService;
use App\Services\CandidateExperience\CandidateSupportDashboardService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Visits\VisitCalendarService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class VisitOperationalMunicipalScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_visit_records_are_isolated_by_canonical_municipality(): void
    {
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();
        $actorA = User::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);
        $chainA = $this->visitChain($municipalityA);
        $chainB = $this->visitChain($municipalityB);
        $scope = app(MunicipalRecordScopeService::class);

        $this->assertSame(
            [$chainA['availability']->id],
            $scope->visitAvailabilities(
                VisitAvailability::query(),
                $actorA,
            )->pluck('id')->all(),
        );
        $this->assertSame(
            [$chainA['slot']->id],
            $scope->visitSlots(
                VisitSlot::query(),
                $actorA,
            )->pluck('id')->all(),
        );
        $this->assertSame(
            [$chainA['visit']->id],
            $scope->housingVisits(
                HousingVisit::query(),
                $actorA,
            )->pluck('id')->all(),
        );

        $this->assertTrue(
            $scope->ownsVisitAvailability(
                $actorA,
                $chainA['availability'],
            ),
        );
        $this->assertFalse(
            $scope->ownsVisitAvailability(
                $actorA,
                $chainB['availability'],
            ),
        );
        $this->assertTrue(
            $scope->ownsVisitSlot($actorA, $chainA['slot']),
        );
        $this->assertFalse(
            $scope->ownsVisitSlot($actorA, $chainB['slot']),
        );
        $this->assertTrue(
            $scope->ownsHousingVisit($actorA, $chainA['visit']),
        );
        $this->assertFalse(
            $scope->ownsHousingVisit($actorA, $chainB['visit']),
        );
        $this->assertSame(
            [$chainA['visit']->id],
            app(VisitCalendarService::class)
                ->backofficeCalendar($actorA)
                ->pluck('id')
                ->all(),
        );
    }

    public function test_scope_fails_closed_without_municipality_or_with_invalid_operator_assignment(): void
    {
        $municipality = Municipality::factory()->create();
        $chain = $this->visitChain($municipality);
        $withoutMunicipality = User::factory()
            ->withoutMunicipality()
            ->create();
        $revokedOperator = User::factory()
            ->withoutMunicipality()
            ->create(['status' => 'active']);
        PlatformOperatorAssignment::factory()
            ->revoked()
            ->for($revokedOperator)
            ->create();
        $inactiveOperator = User::factory()
            ->withoutMunicipality()
            ->create(['status' => 'active']);
        PlatformOperatorAssignment::factory()
            ->for($inactiveOperator)
            ->create();
        $inactiveOperator->update(['status' => 'inactive']);
        $scope = app(MunicipalRecordScopeService::class);

        foreach (
            [
                $withoutMunicipality,
                $revokedOperator,
                $inactiveOperator,
            ] as $actor
        ) {
            $this->assertSame(
                0,
                $scope->visitAvailabilities(
                    VisitAvailability::query(),
                    $actor,
                )->count(),
            );
            $this->assertSame(
                0,
                $scope->visitSlots(
                    VisitSlot::query(),
                    $actor,
                )->count(),
            );
            $this->assertSame(
                0,
                $scope->housingVisits(
                    HousingVisit::query(),
                    $actor,
                )->count(),
            );
            $this->assertFalse(
                $scope->ownsVisitAvailability(
                    $actor,
                    $chain['availability'],
                ),
            );
            $this->assertFalse(
                $scope->ownsVisitSlot($actor, $chain['slot']),
            );
            $this->assertFalse(
                $scope->ownsHousingVisit($actor, $chain['visit']),
            );
        }
    }

    public function test_active_platform_operator_sees_only_structurally_valid_municipal_records(): void
    {
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();
        $chainA = $this->visitChain($municipalityA);
        $chainB = $this->visitChain($municipalityB);
        $operator = User::factory()
            ->withoutMunicipality()
            ->create(['status' => 'active']);
        PlatformOperatorAssignment::factory()
            ->for($operator)
            ->create();

        $nullAvailability = VisitAvailability::factory()->create([
            'contest_id' => $chainA['contest']->id,
        ]);
        $nullAvailability->forceFill([
            'municipality_id' => null,
        ])->saveQuietly();

        $contradictorySlot = VisitSlot::factory()->create([
            'visit_availability_id' => $chainA['availability']->id,
            'contest_id' => $chainB['contest']->id,
        ]);
        $contradictorySlot->forceFill([
            'municipality_id' => $municipalityA->id,
        ])->saveQuietly();

        $contradictoryVisit = HousingVisit::factory()->create([
            'visit_slot_id' => $chainA['slot']->id,
            'contest_id' => $chainB['contest']->id,
        ]);
        $contradictoryVisit->forceFill([
            'municipality_id' => $municipalityA->id,
        ])->saveQuietly();

        $scope = app(MunicipalRecordScopeService::class);

        $this->assertEqualsCanonicalizing(
            [
                $chainA['availability']->id,
                $chainB['availability']->id,
            ],
            $scope->visitAvailabilities(
                VisitAvailability::query(),
                $operator,
            )->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$chainA['slot']->id, $chainB['slot']->id],
            $scope->visitSlots(
                VisitSlot::query(),
                $operator,
            )->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$chainA['visit']->id, $chainB['visit']->id],
            $scope->housingVisits(
                HousingVisit::query(),
                $operator,
            )->pluck('id')->all(),
        );

        $this->assertFalse(
            $scope->ownsVisitAvailability(
                $operator,
                $nullAvailability,
            ),
        );
        $this->assertFalse(
            $scope->ownsVisitSlot($operator, $contradictorySlot),
        );
        $this->assertFalse(
            $scope->ownsHousingVisit(
                $operator,
                $contradictoryVisit,
            ),
        );
    }

    public function test_visit_dashboard_metrics_are_scoped_before_aggregation(): void
    {
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();
        $actorA = User::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);
        $withoutMunicipality = User::factory()
            ->withoutMunicipality()
            ->create();
        $chainA = $this->visitChain($municipalityA);
        $chainB = $this->visitChain($municipalityB);

        $chainA['visit']->forceFill([
            'status' => VisitStatus::Confirmed->value,
        ])->saveQuietly();
        $chainB['visit']->forceFill([
            'status' => VisitStatus::Confirmed->value,
        ])->saveQuietly();
        $chainA['slot']->forceFill([
            'status' => VisitSlotStatus::Available->value,
        ])->saveQuietly();
        $chainB['slot']->forceFill([
            'status' => VisitSlotStatus::Available->value,
        ])->saveQuietly();

        $statistics = app(VisitStatisticsService::class);
        $supportDashboard = app(
            CandidateSupportDashboardService::class,
        );

        $this->assertSame(
            1,
            $statistics->summary($actorA)['confirmed'],
        );
        $this->assertSame(
            1,
            $supportDashboard->indicators($actorA)[
                'visits_confirmed'
            ],
        );
        $this->assertSame(
            1,
            $supportDashboard->indicators($actorA)[
                'slots_available'
            ],
        );
        $this->assertSame(
            0,
            $statistics->summary($withoutMunicipality)[
                'confirmed'
            ],
        );
        $this->assertSame(
            0,
            $supportDashboard->indicators(
                $withoutMunicipality,
            )['visits_confirmed'],
        );
    }

    public function test_backoffice_visit_pages_enforce_permission_mfa_and_municipal_scope(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();
        $staffA = User::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);
        $staffA->assignRole('municipal_technician');
        $chainA = $this->visitChain($municipalityA, $staffA);
        $chainB = $this->visitChain($municipalityB);

        $chainA['availability']->forceFill([
            'title' => 'Disponibilidade municipal A',
        ])->saveQuietly();
        $chainB['availability']->forceFill([
            'title' => 'Disponibilidade municipal B',
        ])->saveQuietly();

        $this->actingAs($staffA)
            ->get(route('backoffice.visit-availabilities.index'))
            ->assertRedirect();

        $this->actingAs($staffA)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.visit-availabilities.index'))
            ->assertOk()
            ->assertSee($chainA['availability']->title)
            ->assertDontSee($chainB['availability']->title);

        $this->actingAs($staffA)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.visit-availabilities.show',
                $chainB['availability'],
            ))
            ->assertForbidden();

        $withoutPermission = User::factory()->create([
            'municipality_id' => $municipalityA->id,
        ]);
        $this->actingAs($withoutPermission)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.visit-availabilities.index'))
            ->assertForbidden();

        $candidate = User::factory()->create([
            'municipality_id' => null,
        ]);
        $candidate->assignRole('candidate');
        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.visit-availabilities.index'))
            ->assertForbidden();
    }

    public function test_active_global_operator_can_use_backoffice_but_revoked_operator_fails_closed(): void
    {
        $this->seed(SystemAccessSeeder::class);
        $municipalityA = Municipality::factory()->create();
        $municipalityB = Municipality::factory()->create();
        $chainA = $this->visitChain($municipalityA);
        $chainB = $this->visitChain($municipalityB);

        $activeOperator = User::factory()
            ->withoutMunicipality()
            ->create(['status' => 'active']);
        $activeOperator->assignRole('administrator');
        PlatformOperatorAssignment::factory()
            ->for($activeOperator)
            ->create();

        $revokedOperator = User::factory()
            ->withoutMunicipality()
            ->create(['status' => 'active']);
        $revokedOperator->assignRole('administrator');
        PlatformOperatorAssignment::factory()
            ->revoked()
            ->for($revokedOperator)
            ->create();

        $this->actingAs($activeOperator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.visit-availabilities.index'))
            ->assertOk()
            ->assertSee($chainA['availability']->title)
            ->assertSee($chainB['availability']->title);

        $this->actingAs($revokedOperator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.visit-availabilities.index'))
            ->assertForbidden();
    }

    /**
     * @return array{
     *     contest: Contest,
     *     availability: VisitAvailability,
     *     slot: VisitSlot,
     *     visit: HousingVisit
     * }
     */
    private function visitChain(
        Municipality $municipality,
        ?User $staff = null,
    ): array {
        $program = Program::factory()->create([
            'municipality_id' => $municipality->id,
        ]);
        $contest = Contest::factory()->create([
            'program_id' => $program->id,
        ]);
        $availability = VisitAvailability::factory()->create([
            'contest_id' => $contest->id,
            'staff_user_id' => $staff?->id,
        ]);
        $slot = VisitSlot::factory()->create([
            'visit_availability_id' => $availability->id,
            'contest_id' => $contest->id,
            'staff_user_id' => $staff?->id,
        ]);
        $visit = HousingVisit::factory()->create([
            'visit_slot_id' => $slot->id,
            'contest_id' => $contest->id,
            'staff_user_id' => $staff?->id,
        ]);

        return [
            'contest' => $contest,
            'availability' => $availability,
            'slot' => $slot,
            'visit' => $visit,
        ];
    }
}
