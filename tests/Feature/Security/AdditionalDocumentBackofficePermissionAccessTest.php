<?php

namespace Tests\Feature\Security;

use App\Models\AdditionalDocumentRequest;
use App\Models\AdditionalDocumentSubmission;
use App\Models\Application;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdditionalDocumentBackofficePermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_additional_document_routes_use_expected_permissions(): void
    {
        $expected = [
            'backoffice.additional-document-requests.index'
                => 'permission:documents.view,applications.view',

            'backoffice.additional-document-requests.store'
                => 'permission:documents.create,applications.update',

            'backoffice.additional-document-submissions.index'
                => 'permission:documents.view,applications.view',

            'backoffice.additional-document-submissions.show'
                => 'permission:documents.view,applications.view',

            'backoffice.additional-document-submissions.decide'
                => 'permission:documents.approve,documents.reject',
        ];

        foreach ($expected as $routeName => $permissionMiddleware) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull(
                $route,
                "Route [{$routeName}] is not registered.",
            );

            $this->assertContains(
                self::FIXED_ROLE_MIDDLEWARE,
                $route->excludedMiddleware(),
            );

            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );

            $this->assertFalse(
                collect($middleware)->contains(
                    fn (string $item): bool => str_starts_with($item, 'role:')
                ),
            );

            $this->assertContains($permissionMiddleware, $middleware);
            $this->assertContains('active.backoffice', $middleware);
            $this->assertContains('mfa.backoffice', $middleware);
            $this->assertContains('log.backoffice', $middleware);
        }
    }

    public function test_custom_role_with_documents_view_can_access_request_index(): void
    {
        $user = $this->userWithCustomRole([
            'documents.view',
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.additional-document-requests.index'))
            ->assertOk();
    }

    public function test_custom_role_with_applications_view_can_access_request_index(): void
    {
        $user = $this->userWithCustomRole([
            'applications.view',
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.additional-document-requests.index'))
            ->assertOk();
    }

    public function test_user_without_view_permissions_cannot_access_request_index(): void
    {
        $user = $this->userWithCustomRole([
            'documents.create',
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.additional-document-requests.index'))
            ->assertForbidden();
    }

    public function test_custom_role_with_documents_create_reaches_request_creation(): void
    {
        $user = $this->userWithCustomRole([
            'documents.create',
        ]);

        $application = Application::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route(
                    'backoffice.additional-document-requests.store',
                    $application,
                ),
                [
                    'title' => 'Comprovativo adicional',
                    'description' => 'Documento necessário para completar a análise.',
                    'due_at' => now()->addDays(5)->toDateTimeString(),
                ],
            );

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_custom_role_with_applications_update_reaches_request_creation(): void
    {
        $user = $this->userWithCustomRole([
            'applications.update',
        ]);

        $application = Application::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route(
                    'backoffice.additional-document-requests.store',
                    $application,
                ),
                [
                    'title' => 'Declaração complementar',
                    'due_at' => now()->addDays(5)->toDateTimeString(),
                ],
            );

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_custom_role_with_documents_view_can_access_submission_index(): void
    {
        $user = $this->userWithCustomRole([
            'documents.view',
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.additional-document-submissions.index'))
            ->assertOk();
    }

    public function test_custom_role_with_applications_view_can_access_submission_show(): void
    {
        $user = $this->userWithCustomRole([
            'applications.view',
        ]);

        $submission = AdditionalDocumentSubmission::factory()->create();

        $this->actingAs($user)
            ->get(route(
                'backoffice.additional-document-submissions.show',
                $submission,
            ))
            ->assertOk();
    }

    public function test_custom_role_with_documents_approve_reaches_submission_decision(): void
    {
        $user = $this->userWithCustomRole([
            'documents.approve',
        ]);

        $submission = AdditionalDocumentSubmission::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route(
                    'backoffice.additional-document-submissions.decide',
                    $submission,
                ),
                [
                    'accepted' => true,
                ],
            );

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_custom_role_with_documents_reject_reaches_submission_decision(): void
    {
        $user = $this->userWithCustomRole([
            'documents.reject',
        ]);

        $submission = AdditionalDocumentSubmission::factory()->create();

        $response = $this->actingAs($user)
            ->post(
                route(
                    'backoffice.additional-document-submissions.decide',
                    $submission,
                ),
                [
                    'accepted' => false,
                    'rejection_reason' => 'Documento ilegível.',
                ],
            );

        $this->assertNotSame(403, $response->getStatusCode());
    }

    public function test_candidate_cannot_access_backoffice_requests_even_with_permissions(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'documents.view',
                'documents.create',
                'applications.view',
                'applications.update',
            ],
        );

        $application = Application::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.additional-document-requests.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(
                route(
                    'backoffice.additional-document-requests.store',
                    $application,
                ),
                [
                    'title' => 'Tentativa indevida',
                    'due_at' => now()->addDays(5)->toDateTimeString(),
                ],
            )
            ->assertForbidden();
    }

    public function test_candidate_cannot_access_backoffice_submissions_even_with_permissions(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'candidate',
            permissions: [
                'documents.view',
                'documents.approve',
                'documents.reject',
                'applications.view',
            ],
        );

        $submission = AdditionalDocumentSubmission::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route(
                'backoffice.additional-document-submissions.show',
                $submission,
            ))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(
                route(
                    'backoffice.additional-document-submissions.decide',
                    $submission,
                ),
                [
                    'accepted' => true,
                ],
            )
            ->assertForbidden();
    }

    public function test_auditor_cannot_create_request_or_decide_submission_even_with_permissions(): void
    {
        $user = $this->userWithSystemRoleAndPermissions(
            roleName: 'auditor',
            permissions: [
                'documents.create',
                'documents.approve',
                'documents.reject',
                'applications.update',
            ],
        );

        $application = Application::factory()->create();
        $submission = AdditionalDocumentSubmission::factory()->create();

        $this->actingAs($user)
            ->withSession([
                'mfa.verified_at' => now(),
            ])
            ->post(
                route(
                    'backoffice.additional-document-requests.store',
                    $application,
                ),
                [
                    'title' => 'Pedido indevido',
                    'due_at' => now()->addDays(5)->toDateTimeString(),
                ],
            )
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession([
                'mfa.verified_at' => now(),
            ])
            ->post(
                route(
                    'backoffice.additional-document-submissions.decide',
                    $submission,
                ),
                [
                    'accepted' => true,
                ],
            )
            ->assertForbidden();
    }

    /**
     * @param list<string> $permissions
     */
    private function userWithCustomRole(array $permissions): User
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'name' => 'additional_documents_'.str()->random(8),
            'label' => 'Additional documents test role',
            'scope' => 'municipal',
            'is_system' => false,
        ]);

        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');

        $this->assertCount(count($permissions), $permissionIds);

        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }

    /**
     * @param list<string> $permissions
     */
    private function userWithSystemRoleAndPermissions(
        string $roleName,
        array $permissions,
    ): User {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $role = Role::query()
            ->where('name', $roleName)
            ->firstOrFail();

        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');

        $this->assertCount(count($permissions), $permissionIds);

        $role->permissions()->syncWithoutDetaching($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }
}
