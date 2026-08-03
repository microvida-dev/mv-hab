<?php

namespace Tests\Feature\Backoffice;

use App\Models\Municipality;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use App\Services\Access\RoleAssignmentService;
use App\Services\Access\RoleManagementService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContestOperationsRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_two_municipal_users_receive_the_same_template_role_and_effective_permissions(): void
    {
        $municipality = Municipality::factory()->create();
        $administrator = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
        $administrator->assignRole('administrator');

        $firstTechnician = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
        $secondTechnician = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);

        $role = app(RoleManagementService::class)->applyTemplate(
            $administrator,
            'tecnico-operacoes-concurso',
            'Criação controlada do perfil operacional comum do concurso.',
        );

        $assignments = app(RoleAssignmentService::class);
        $assignments->assign(
            $administrator,
            $firstTechnician,
            $role,
            'Atribuição ao primeiro técnico de operações do concurso.',
        );
        $assignments->assign(
            $administrator,
            $secondTechnician,
            $role,
            'Atribuição ao segundo técnico de operações do concurso.',
        );

        $template = app(MunicipalRoleTemplateRegistry::class)
            ->resolve('tecnico-operacoes-concurso');
        $storedRole = Role::query()
            ->where('municipality_id', $municipality->id)
            ->where('template_key', 'tecnico-operacoes-concurso')
            ->sole();

        $this->assertSame($role->id, $storedRole->id);
        $this->assertSame(2, $storedRole->users()->count());
        $this->assertEqualsCanonicalizing(
            $template['permissions'],
            $storedRole->permissions()->pluck('name')->all(),
        );
        $this->assertEqualsCanonicalizing(
            $firstTechnician->roles()->pluck('roles.id')->all(),
            $secondTechnician->roles()->pluck('roles.id')->all(),
        );
        $this->assertTrue($firstTechnician->hasPermission('documents.approve'));
        $this->assertTrue($secondTechnician->hasPermission('documents.approve'));
        $this->assertTrue($firstTechnician->hasPermission('reports.export_sensitive'));
        $this->assertTrue($secondTechnician->hasPermission('reports.export_sensitive'));
        $this->assertFalse($firstTechnician->hasPermission('roles.assign'));
        $this->assertFalse($secondTechnician->hasPermission('roles.assign'));
        $this->assertFalse($firstTechnician->hasPermission('finance.approve'));
        $this->assertFalse($secondTechnician->hasPermission('finance.approve'));
    }
}
