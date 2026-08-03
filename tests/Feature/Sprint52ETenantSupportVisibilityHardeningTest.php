<?php

namespace Tests\Feature;

use App\Enums\ContractStatus;
use App\Enums\KeyHandoverStatus;
use App\Enums\TenantPortalStatus;
use App\Enums\TenantTransitionStatus;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\CandidateExperience\CandidateNavigationService;
use App\Services\CandidateExperience\TenantSupportEligibilityService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\Support\CreatesTenantSupportEligibility;
use Tests\TestCase;

class Sprint52ETenantSupportVisibilityHardeningTest extends TestCase
{
    use CreatesTenantSupportEligibility, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_all_candidate_support_routes_keep_the_authoritative_tenant_support_guard(): void
    {
        foreach ($this->candidateSupportRoutes() as $name) {
            $route = Route::getRoutes()->getByName($name);

            self::assertNotNull($route, "Rota {$name} não registada.");
            self::assertContains('tenant.support', $route->middleware());
        }
    }

    public function test_support_is_hidden_and_policy_fails_closed_before_the_complete_tenant_lifecycle(): void
    {
        $candidate = $this->candidate();
        $ticket = SupportTicket::factory()->create([
            'user_id' => $candidate->id,
        ]);

        self::assertNotContains('Apoio', $this->navigationLabels($candidate));
        self::assertFalse(
            Gate::forUser($candidate)->allows(
                'viewAny',
                SupportTicket::class,
            ),
        );
        self::assertFalse(
            Gate::forUser($candidate)->allows(
                'create',
                SupportTicket::class,
            ),
        );
        self::assertFalse(
            Gate::forUser($candidate)->allows('view', $ticket),
        );
        self::assertFalse(
            Gate::forUser($candidate)->allows('update', $ticket),
        );

        $this->actingAs($candidate)
            ->get(route('candidate.support-tickets.index'))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->post(route('candidate.support-tickets.store'), [
                'category' => 'application',
                'subject' => 'Pedido que não pode ser criado',
                'description' => 'O ciclo de inquilino ainda não está completo.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('support_tickets', 1);
    }

    public function test_each_authoritative_lifecycle_precondition_is_required(): void
    {
        $candidate = $this->candidate();
        $transition = $this->enableTenantSupportFor($candidate);
        $eligibility = app(TenantSupportEligibilityService::class);
        $profile = $candidate->tenantProfile()->firstOrFail();
        $contract = $transition->leaseContract()->firstOrFail();
        $handover = $transition->keyHandoverAppointment()->firstOrFail();

        self::assertTrue($eligibility->isAvailableFor($candidate));

        $profile->forceFill([
            'status' => TenantPortalStatus::Blocked->value,
        ])->save();
        self::assertFalse($eligibility->isAvailableFor($candidate));
        $profile->forceFill([
            'status' => TenantPortalStatus::Active->value,
        ])->save();

        $profile->forceFill(['activated_at' => null])->save();
        self::assertFalse($eligibility->isAvailableFor($candidate));
        $profile->forceFill(['activated_at' => now()])->save();

        $transition->forceFill([
            'status' => TenantTransitionStatus::Pending->value,
        ])->save();
        self::assertFalse($eligibility->isAvailableFor($candidate));
        $transition->forceFill([
            'status' => TenantTransitionStatus::Completed->value,
        ])->save();

        $transition->forceFill(['completed_at' => null])->save();
        self::assertFalse($eligibility->isAvailableFor($candidate));
        $transition->forceFill(['completed_at' => now()])->save();

        $contract->forceFill([
            'status' => ContractStatus::Signed->value,
        ])->save();
        self::assertFalse($eligibility->isAvailableFor($candidate));
        $contract->forceFill([
            'status' => ContractStatus::Active->value,
        ])->save();

        $contract->forceFill(['activated_at' => null])->save();
        self::assertFalse($eligibility->isAvailableFor($candidate));
        $contract->forceFill(['activated_at' => now()])->save();

        $handover->forceFill([
            'status' => KeyHandoverStatus::Scheduled->value,
        ])->save();
        self::assertFalse($eligibility->isAvailableFor($candidate));
        $handover->forceFill([
            'status' => KeyHandoverStatus::Completed->value,
        ])->save();

        $handover->forceFill(['completed_at' => null])->save();
        self::assertFalse($eligibility->isAvailableFor($candidate));
        $handover->forceFill(['completed_at' => now()])->save();

        self::assertTrue($eligibility->isAvailableFor($candidate));
    }

    public function test_complete_lifecycle_unlocks_navigation_route_and_policy_together(): void
    {
        $candidate = $this->candidate();
        $this->enableTenantSupportFor($candidate);

        self::assertTrue(
            app(TenantSupportEligibilityService::class)
                ->isAvailableFor($candidate),
        );
        self::assertContains('Apoio', $this->navigationLabels($candidate));
        self::assertTrue(
            Gate::forUser($candidate)->allows(
                'viewAny',
                SupportTicket::class,
            ),
        );
        self::assertTrue(
            Gate::forUser($candidate)->allows(
                'create',
                SupportTicket::class,
            ),
        );

        $this->actingAs($candidate)
            ->get(route('candidate.support-tickets.index'))
            ->assertOk();
    }

    public function test_runtime_kill_switch_closes_navigation_route_and_policy_for_an_active_tenant(): void
    {
        $candidate = $this->candidate();
        $this->enableTenantSupportFor($candidate);

        config()->set(
            'mvhab.candidate_experience_runtime.tenant_support',
            false,
        );

        self::assertFalse(
            app(TenantSupportEligibilityService::class)
                ->isAvailableFor($candidate),
        );
        self::assertNotContains('Apoio', $this->navigationLabels($candidate));
        self::assertFalse(
            Gate::forUser($candidate)->allows(
                'viewAny',
                SupportTicket::class,
            ),
        );

        $this->actingAs($candidate)
            ->get(route('candidate.support-tickets.index'))
            ->assertForbidden();
    }

    public function test_historical_ticket_is_preserved_when_tenant_support_becomes_unavailable(): void
    {
        $candidate = $this->candidate();
        $transition = $this->enableTenantSupportFor($candidate);
        $ticket = SupportTicket::factory()->create([
            'user_id' => $candidate->id,
        ]);
        $contract = $transition->leaseContract()->firstOrFail();

        $contract->forceFill([
            'status' => ContractStatus::Terminated->value,
            'terminated_at' => now(),
        ])->save();

        $this->actingAs($candidate)
            ->get(route('candidate.support-tickets.show', $ticket))
            ->assertForbidden();

        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'user_id' => $candidate->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * @return list<string>
     */
    private function candidateSupportRoutes(): array
    {
        return [
            'candidate.support-tickets.index',
            'candidate.support-tickets.create',
            'candidate.support-tickets.store',
            'candidate.support-tickets.show',
            'candidate.support-ticket-messages.store',
            'candidate.support-ticket-attachments.download',
        ];
    }

    /**
     * @return list<string>
     */
    private function navigationLabels(User $candidate): array
    {
        $navigation = app(CandidateNavigationService::class)
            ->forUser($candidate);
        $labels = [];

        foreach ($navigation['groups'] as $links) {
            foreach ($links as $link) {
                $labels[] = $link['label'];
            }
        }

        return $labels;
    }

    private function candidate(): User
    {
        $candidate = User::factory()->create();
        $candidate->assignRole('candidate');

        return $candidate;
    }
}
