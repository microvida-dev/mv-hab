<?php

namespace Tests\Feature\Backoffice;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\PermissionCatalogService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_matrix_groups_permissions_with_portuguese_labels_and_technical_codes(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.roles.create'))
            ->assertOk()
            ->assertSeeInOrder(['Candidaturas', 'Consultar candidaturas', 'applications.view'])
            ->assertSeeInOrder(['Documentos', 'Validar documentos', 'documents.approve'])
            ->assertSee('Pesquisar permissões')
            ->assertSee('Selecionar domínio')
            ->assertSee('Sensível');
    }

    public function test_catalog_has_a_safe_unknown_permission_fallback(): void
    {
        $metadata = app(PermissionCatalogService::class)
            ->metadata('legacy_module.review_archive', 'legacy_module', 'review_archive');

        $this->assertSame('Outros', $metadata['domain_label']);
        $this->assertStringContainsString('Ação não catalogada', $metadata['label']);
        $this->assertFalse($metadata['sensitive']);
    }

    public function test_selected_permissions_are_preserved_after_validation_error(): void
    {
        $administrator = $this->administrator();
        $permission = Permission::query()->where('name', 'applications.view')->firstOrFail();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->from(route('backoffice.roles.create'))
            ->post(route('backoffice.roles.store'), [
                'label' => '',
                'permissions' => [$permission->id],
                'justification' => 'Teste de preservação da matriz.',
            ])
            ->assertRedirect(route('backoffice.roles.create'))
            ->assertSessionHasErrors('label')
            ->assertSessionHasInput('permissions', [$permission->id]);

        $this->get(route('backoffice.roles.create'))
            ->assertOk()
            ->assertSee('value="'.$permission->id.'"', false)
            ->assertSee('checked', false);
    }

    public function test_system_role_presents_a_read_only_permission_matrix(): void
    {
        $administrator = $this->administrator();
        $role = Role::query()->where('name', 'municipal_technician')->firstOrFail();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.roles.show', $role))
            ->assertOk()
            ->assertSee('Matriz de permissões')
            ->assertSee('disabled', false)
            ->assertDontSee('Selecionar domínio');
    }

    public function test_permission_ids_are_unique_and_invalid_ids_are_rejected(): void
    {
        $administrator = $this->administrator();
        $permission = Permission::query()->where('name', 'applications.view')->firstOrFail();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.roles.store'), [
                'label' => 'Perfil inválido',
                'permissions' => [$permission->id, $permission->id, 999999],
                'justification' => 'Teste de validação da matriz.',
            ])
            ->assertSessionHasErrors('permissions.1');

        $this->assertDatabaseMissing('roles', ['label' => 'Perfil inválido']);
    }

    private function administrator(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('administrator');

        return $user;
    }
}
