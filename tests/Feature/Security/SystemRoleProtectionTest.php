<?php

namespace Tests\Feature\Security;

use App\Models\Role;
use App\Models\User;
use App\Services\Access\RoleManagementService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemRoleProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_system_role_cannot_be_updated_by_service(): void
    {
        $administrator = $this->administrator();
        $systemRole = Role::query()->where('name', 'municipal_technician')->firstOrFail();

        $this->expectException(AuthorizationException::class);

        app(RoleManagementService::class)->update(
            $administrator,
            $systemRole,
            ['label' => 'Alteração proibida', 'description' => null],
            [],
            'Tentativa controlada sobre perfil de sistema.',
        );
    }

    public function test_system_role_cannot_be_deactivated_or_deleted_by_service(): void
    {
        $administrator = $this->administrator();
        $systemRole = Role::query()->where('name', 'municipal_technician')->firstOrFail();
        $service = app(RoleManagementService::class);

        try {
            $service->deactivate($administrator, $systemRole, 'Tentativa de desativação controlada.');
            $this->fail('A role de sistema foi desativada.');
        } catch (AuthorizationException) {
            $this->assertTrue($systemRole->refresh()->isActive());
        }

        try {
            $service->delete($administrator, $systemRole, 'Tentativa de eliminação controlada.');
            $this->fail('A role de sistema foi eliminada.');
        } catch (AuthorizationException) {
            $this->assertDatabaseHas('roles', ['id' => $systemRole->id]);
        }
    }

    public function test_manipulated_payload_does_not_change_protected_role_attributes(): void
    {
        $administrator = $this->administrator();
        $permission = $administrator->roles()->firstOrFail()->permissions()->firstOrFail();
        $role = Role::query()->create([
            'name' => 'operador_municipal_estavel',
            'label' => 'Operador municipal estável',
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ]);

        app(RoleManagementService::class)->update(
            $administrator,
            $role,
            [
                'label' => 'Operador municipal atualizado',
                'description' => 'Descrição segura.',
                'name' => 'administrator',
                'scope' => 'system',
                'is_system' => true,
            ],
            [$permission->id],
            'Atualização funcional sem alterar o identificador.',
        );

        $role->refresh();

        $this->assertSame('operador_municipal_estavel', $role->name);
        $this->assertSame('municipal', $role->scope);
        $this->assertFalse($role->isSystem());
        $this->assertSame('Operador municipal atualizado', $role->label);
    }

    public function test_system_role_can_be_duplicated_as_independent_municipal_role(): void
    {
        $administrator = $this->administrator();
        $source = Role::query()->where('name', 'municipal_technician')->firstOrFail();

        $copy = app(RoleManagementService::class)->duplicate(
            $administrator,
            $source,
            'Técnico municipal adaptado',
            'Perfil municipal derivado para testes.',
            'Criação de perfil municipal a partir do perfil técnico.',
        );

        $this->assertTrue($copy->isMunicipalCustom());
        $this->assertTrue($copy->isActive());
        $this->assertNotSame($source->name, $copy->name);
        $this->assertSame(0, $copy->users()->count());
        $this->assertEqualsCanonicalizing(
            $source->permissions()->pluck('name')->all(),
            $copy->permissions()->pluck('name')->all(),
        );
    }

    private function administrator(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('administrator');

        return $user;
    }
}
