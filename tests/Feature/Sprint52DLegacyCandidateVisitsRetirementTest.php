<?php

namespace Tests\Feature;

use App\Enums\VisitCancellationReason;
use App\Enums\VisitStatus;
use App\Models\HousingVisit;
use App\Models\User;
use App\Services\Visits\VisitCancellationService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Sprint52DLegacyCandidateVisitsRetirementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_candidate_visit_routes_cannot_be_reactivated_by_configuration(): void
    {
        config([
            'mvhab.candidate_experience_runtime.legacy_visits' => true,
        ]);

        foreach ($this->retiredRouteNames() as $routeName) {
            $this->assertFalse(Route::has($routeName));
        }

        $candidate = $this->candidate();

        $this->actingAs($candidate)
            ->get('/area-candidato/visitas')
            ->assertNotFound();

        $this->actingAs($candidate)
            ->post('/area-candidato/visitas', [])
            ->assertNotFound();

        $this->assertDatabaseCount('housing_visits', 0);
    }

    public function test_candidate_role_no_longer_receives_legacy_visit_permissions(): void
    {
        $candidate = $this->candidate();

        $this->assertFalse($candidate->hasPermission('visits.view'));
        $this->assertFalse($candidate->hasPermission('visits.create'));
        $this->assertFalse($candidate->hasPermission('visits.update'));
    }

    public function test_candidate_policy_access_to_historical_visits_is_fail_closed(): void
    {
        $candidate = $this->candidate();
        $visit = HousingVisit::factory()->create([
            'candidate_user_id' => $candidate->id,
        ]);

        $this->assertFalse(
            Gate::forUser($candidate)->allows('view', $visit),
        );
        $this->assertFalse(
            Gate::forUser($candidate)->allows('update', $visit),
        );
        $this->assertFalse(
            Gate::forUser($candidate)->allows('cancel', $visit),
        );
        $this->assertFalse(
            Gate::forUser($candidate)->allows('create', HousingVisit::class),
        );
    }

    public function test_candidate_cannot_cancel_historical_visit_through_the_domain_service(): void
    {
        $candidate = $this->candidate();
        $visit = HousingVisit::factory()->create([
            'candidate_user_id' => $candidate->id,
        ]);

        try {
            app(VisitCancellationService::class)->cancel(
                $visit,
                $candidate,
                VisitCancellationReason::CandidateUnavailable,
                'Tentativa candidate após retirada do fluxo.',
            );

            $this->fail('O cancelamento candidate legacy deveria falhar fechado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('visit', $exception->errors());
        }

        $this->assertSame(
            VisitStatus::PendingConfirmation,
            $visit->refresh()->status,
        );
    }

    public function test_backoffice_can_still_close_historical_visit(): void
    {
        $visit = HousingVisit::factory()->create();
        $staff = User::factory()->create([
            'municipality_id' => $visit->municipality_id,
        ]);
        $staff->assignRole('municipal_technician');

        $cancelled = app(VisitCancellationService::class)->cancel(
            $visit,
            $staff,
            VisitCancellationReason::CandidateUnavailable,
            'Encerramento administrativo de registo legacy.',
        );

        $this->assertSame(
            VisitStatus::CancelledByStaff,
            $cancelled->status,
        );
    }

    public function test_public_and_backoffice_visit_surfaces_remain_registered(): void
    {
        foreach ([
            'public.visit-bookings.store',
            'public.visit-bookings.confirmed',
            'public.visit-bookings.cancel',
            'public.visit-bookings.destroy',
            'public.visit-bookings.cancelled',
            'backoffice.public-visit-bookings.index',
            'backoffice.housing-visits.index',
            'backoffice.housing-visits.show',
            'backoffice.housing-visits.cancel',
            'backoffice.housing-visits.complete',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName));
        }
    }

    /** @return list<string> */
    private function retiredRouteNames(): array
    {
        return [
            'candidate.visits.index',
            'candidate.visits.create',
            'candidate.visits.store',
            'candidate.visits.show',
            'candidate.visits.reschedule',
            'candidate.visits.reschedule.store',
            'candidate.visits.cancel',
        ];
    }

    private function candidate(): User
    {
        $candidate = User::factory()->withoutMunicipality()->create();
        $candidate->assignRole('candidate');

        return $candidate;
    }
}
