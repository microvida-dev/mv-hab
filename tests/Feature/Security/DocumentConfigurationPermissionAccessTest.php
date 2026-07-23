<?php

namespace Tests\Feature\Security;

use App\Enums\DocumentAppliesTo;
use App\Enums\RequiredDocumentConditionOperator;
use App\Models\DocumentType;
use App\Models\Permission;
use App\Models\RequiredDocument;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DocumentConfigurationPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    private const FIXED_ROLE_MIDDLEWARE =
        'role:administrator,municipal_technician,jury,financial_manager,maintenance_manager,auditor';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_document_configuration_routes_use_permission_middleware_without_fixed_role_middleware(): void
    {
        $expectedPermissions = [
            'admin.document-types.index' => 'permission:documents.view',
            'admin.document-types.create' => 'permission:documents.create',
            'admin.document-types.store' => 'permission:documents.create',
            'admin.document-types.edit' => 'permission:documents.update',
            'admin.document-types.update' => 'permission:documents.update',
            'admin.document-types.destroy' => 'permission:documents.delete',

            'admin.required-documents.index' => 'permission:documents.view',
            'admin.required-documents.create' => 'permission:documents.create',
            'admin.required-documents.store' => 'permission:documents.create',
            'admin.required-documents.edit' => 'permission:documents.update',
            'admin.required-documents.update' => 'permission:documents.update',
            'admin.required-documents.destroy' => 'permission:documents.delete',
        ];

        foreach ($expectedPermissions as $routeName => $permissionMiddleware) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull(
                $route,
                "Route [{$routeName}] is not registered.",
            );

            $this->assertContains(
                self::FIXED_ROLE_MIDDLEWARE,
                $route->excludedMiddleware(),
                "Route [{$routeName}] does not exclude the inherited fixed role middleware.",
            );

            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );

            $this->assertFalse(
                collect($middleware)->contains(
                    fn (string $item): bool => str_starts_with($item, 'role:')
                ),
                "Route [{$routeName}] still contains active fixed role middleware.",
            );

            $this->assertContains('auth', $middleware);
            $this->assertContains('active.backoffice', $middleware);
            $this->assertContains('mfa.backoffice', $middleware);
            $this->assertContains('log.backoffice', $middleware);
            $this->assertContains($permissionMiddleware, $middleware);
        }
    }

    public function test_user_with_view_permission_can_access_document_configuration_indexes(): void
    {
        $user = $this->userWithCustomRole([
            'documents.view',
        ]);

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.document-types.index'))
            ->assertOk();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.required-documents.index'))
            ->assertOk();
    }

    public function test_user_without_view_permission_cannot_access_document_configuration_indexes(): void
    {
        $user = $this->userWithCustomRole([]);

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.document-types.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.required-documents.index'))
            ->assertForbidden();
    }

    public function test_required_document_update_respects_the_update_permission(): void
    {
        $documentType = DocumentType::factory()->create();
        $requiredDocument = RequiredDocument::factory()->create();
        $payload = [
            'document_type_id' => $documentType->id,
            'required_for' => DocumentAppliesTo::Application->value,
            'condition_key' => 'application.is_submitted',
            'condition_operator' => RequiredDocumentConditionOperator::IsTrue->value,
            'condition_value' => null,
            'is_required' => true,
            'is_active' => true,
            'instructions' => 'Documento obrigatório após submissão.',
            'sort_order' => 20,
        ];

        $authorizedUser = $this->userWithCustomRole(['documents.update']);

        $this->actingAs($authorizedUser)
            ->withSession(['mfa.verified_at' => now()])
            ->put(route('admin.required-documents.update', $requiredDocument), $payload)
            ->assertRedirect(route('admin.required-documents.index'));

        $this->assertDatabaseHas('required_documents', [
            'id' => $requiredDocument->id,
            'document_type_id' => $documentType->id,
            'required_for' => DocumentAppliesTo::Application->value,
            'condition_key' => 'application.is_submitted',
            'sort_order' => 20,
        ]);

        $unauthorizedUser = $this->userWithCustomRole([]);

        $this->actingAs($unauthorizedUser)
            ->withSession(['mfa.verified_at' => now()])
            ->put(route('admin.required-documents.update', $requiredDocument), [
                ...$payload,
                'condition_key' => 'unauthorized.change',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('required_documents', [
            'id' => $requiredDocument->id,
            'condition_key' => 'unauthorized.change',
        ]);
    }

    public function test_candidate_is_blocked_even_with_document_permissions(): void
    {
        $candidate = $this->userWithSystemRoleAndPermissions('candidate', [
            'documents.view',
            'documents.create',
            'documents.update',
            'documents.delete',
        ]);

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.document-types.index'))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.required-documents.index'))
            ->assertForbidden();
    }

    public function test_auditor_can_view_but_cannot_change_document_configuration(): void
    {
        $auditor = $this->userWithSystemRoleAndPermissions('auditor', [
            'documents.view',
            'documents.create',
            'documents.update',
            'documents.delete',
        ]);

        $documentType = DocumentType::factory()->create();
        $requiredDocument = RequiredDocument::factory()->create();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.document-types.index'))
            ->assertOk();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.required-documents.index'))
            ->assertOk();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.document-types.create'))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.document-types.edit', $documentType))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('admin.document-types.destroy', $documentType))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.required-documents.create'))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('admin.required-documents.edit', $requiredDocument))
            ->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->delete(route('admin.required-documents.destroy', $requiredDocument))
            ->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithCustomRole(array $permissions): User
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $role = Role::query()->create([
            'name' => 'document_configuration_'.str()->random(8),
            'label' => 'Document configuration test role',
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
     * @param  list<string>  $permissions
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
