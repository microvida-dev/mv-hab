<?php

namespace Tests\Feature\Security;

use App\Enums\CorrectionRequestStatus;
use App\Enums\FeatureKey;
use App\Models\AuditLog;
use App\Models\CorrectionRequest;
use App\Models\Permission;
use App\Models\PlatformOperatorAssignment;
use App\Models\Role;
use App\Models\User;
use App\Services\Administrative\CandidateCorrectionWorkspaceService;
use App\Services\Administrative\CorrectionRevalidationService;
use App\Services\Administrative\CorrectionSubmissionService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesPublishedCorrectionRequests;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class Sprint53FCorrectionRevalidationSecurityTest extends TestCase
{
    use CreatesPublishedCorrectionRequests;
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    private const ROUTES = [
        'backoffice.correction-revalidations.index' => 'permission:administrative_processes.view',
        'backoffice.correction-revalidations.start' => 'permission:administrative_processes.update',
        'backoffice.correction-revalidations.decide' => 'permission:administrative_processes.decide',
        'backoffice.correction-revalidations.preview' => 'permission:administrative_processes.update',
        'backoffice.correction-revalidations.seal' => 'permission:administrative_processes.update',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        Storage::fake('local');
        Queue::fake();
    }

    public function test_routes_are_permission_first_and_have_complete_backoffice_guards(): void
    {
        foreach (self::ROUTES as $routeName => $permission) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, $routeName);
            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );

            foreach (['auth', 'active.backoffice', 'mfa.backoffice', 'log.backoffice'] as $guard) {
                $this->assertContains($guard, $middleware, $routeName);
            }

            $this->assertContains($permission, $middleware, $routeName);
            $this->assertContains('municipality.feature:applications.review', $middleware, $routeName);
            $this->assertFalse(collect($middleware)->contains(
                static fn (string $item): bool => str_starts_with($item, 'role:'),
            ), $routeName);
        }
    }

    public function test_permission_without_scope_and_scope_without_permission_fail_closed(): void
    {
        [$request] = $this->submittedRequest();
        $permissionOnly = $this->userWithPermissions(null, [
            'administrative_processes.view',
        ]);
        $scopeOnly = $this->userWithPermissions(
            $request->publicationResult()->firstOrFail()->municipality_id,
            ['dashboard.view'],
        );

        $this->actingAs($permissionOnly)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.correction-revalidations.index'))
            ->assertForbidden();
        $this->actingAs($scopeOnly)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.correction-revalidations.index'))
            ->assertForbidden();

        $this->assertNull(CorrectionRequest::query()
            ->findOrFail($request->id)
            ->revalidation_started_at);
    }

    public function test_municipal_operator_cannot_access_foreign_request(): void
    {
        [$request] = $this->submittedRequest();
        $foreignMunicipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationReview,
        );
        $foreignOperator = $this->userWithPermissions(
            $foreignMunicipality->id,
            [
                'administrative_processes.view',
                'administrative_processes.update',
            ],
        );
        $domainAuditCount = AuditLog::query()
            ->where('action', 'correction_revalidation_started')
            ->count();

        $this->actingAs($foreignOperator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.correction-revalidations.start', $request))
            ->assertForbidden();

        $this->assertNull(CorrectionRequest::query()
            ->findOrFail($request->id)
            ->revalidation_started_at);
        $this->assertSame($domainAuditCount, AuditLog::query()
            ->where('action', 'correction_revalidation_started')
            ->count());
    }

    public function test_candidate_and_auditor_cannot_mutate_revalidation(): void
    {
        [$request] = $this->submittedRequest();
        $municipalityId = $request->publicationResult()->firstOrFail()->municipality_id;
        $candidate = $request->candidate()->firstOrFail();
        $auditor = User::factory()->create([
            'municipality_id' => $municipalityId,
            'status' => 'active',
        ]);
        $auditor->assignRole('auditor');

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.correction-revalidations.start', $request))
            ->assertForbidden();
        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.correction-revalidations.start', $request))
            ->assertForbidden();

        $this->assertNull(CorrectionRequest::query()
            ->findOrFail($request->id)
            ->revalidation_started_at);
    }

    public function test_inactive_account_and_missing_mfa_are_denied_before_mutation(): void
    {
        [$request] = $this->submittedRequest();
        $municipalityId = $request->publicationResult()->firstOrFail()->municipality_id;
        $inactive = $this->userWithPermissions($municipalityId, [
            'administrative_processes.update',
        ]);
        $inactive->forceFill(['status' => 'inactive'])->save();
        $active = $this->userWithPermissions($municipalityId, [
            'administrative_processes.update',
        ]);
        $active->forceFill(['mfa_required' => true])->save();

        $this->actingAs($inactive)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.correction-revalidations.start', $request))
            ->assertForbidden();
        $this->flushSession();
        $mfaResponse = $this->actingAs($active->refresh())
            ->post(route('backoffice.correction-revalidations.start', $request));
        $mfaResponse->assertStatus(302);
        $this->assertSame(
            route('backoffice.security.mfa.index'),
            $mfaResponse->headers->get('Location'),
        );

        $this->assertNull(CorrectionRequest::query()
            ->findOrFail($request->id)
            ->revalidation_started_at);
    }

    public function test_explicit_platform_assignment_is_required_for_global_service_scope(): void
    {
        [$request] = $this->submittedRequest();
        $unassigned = $this->userWithPermissions(null, [
            'administrative_processes.view',
        ]);
        $assigned = $this->userWithPermissions(null, [
            'administrative_processes.view',
        ]);
        PlatformOperatorAssignment::factory()->for($assigned)->create();
        $filters = [
            'contest_id' => null,
            'submitted_from' => '',
            'submitted_to' => '',
            'sla' => '',
            'technician_id' => null,
            'state' => '',
            'result' => '',
            'process_number' => '',
            'application_number' => '',
        ];

        $this->assertSame(0, app(CorrectionRevalidationService::class)
            ->queue($unassigned, $filters)['requests']->total());
        $this->assertSame(1, app(CorrectionRevalidationService::class)
            ->queue($assigned, $filters)['requests']->total());
        $assignedItems = app(CorrectionRevalidationService::class)
            ->queue($assigned, $filters)['requests']->items();
        $this->assertCount(1, $assignedItems);
        $this->assertSame($request->id, $assignedItems[0]->id);
    }

    /** @return array{CorrectionRequest, User} */
    private function submittedRequest(): array
    {
        $municipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationReview,
        );
        $operator = User::factory()->create(['status' => 'active']);
        $operator->assignRole('administrator');
        $request = $this->createPublishedCorrectionRequest(
            municipality: $municipality,
            operator: $operator,
            status: CorrectionRequestStatus::Open,
            completedItems: 0,
            totalItems: 1,
            deadline: now()->addWeek(),
        );
        $candidate = $request->candidate()->firstOrFail();
        app(CandidateCorrectionWorkspaceService::class)->save(
            request: $request->refresh(),
            item: $request->items()->firstOrFail(),
            data: [],
            file: UploadedFile::fake()->create(
                'documento-seguranca.pdf',
                32,
                'application/pdf',
            ),
            candidate: $candidate,
        );
        app(CorrectionSubmissionService::class)->submit(
            $request->refresh(),
            $candidate,
        );

        return [$request->refresh(), $operator->refresh()];
    }

    /** @param list<string> $permissions */
    private function userWithPermissions(
        ?int $municipalityId,
        array $permissions,
    ): User {
        $role = Role::query()->create([
            'name' => 'correction_revalidation_'.str()->random(10),
            'label' => 'Teste de revalidação diferencial',
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ]);
        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');
        $this->assertCount(count($permissions), $permissionIds);
        $role->permissions()->sync($permissionIds);
        $user = User::factory()->create([
            'municipality_id' => $municipalityId,
            'status' => 'active',
        ]);
        $user->roles()->attach($role);

        return $user;
    }
}
