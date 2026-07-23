<?php

namespace Tests\Feature\Security;

use App\Models\AccessChangeEvent;
use App\Models\Municipality;
use App\Models\MunicipalTeam;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministrationMunicipalBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private Municipality $municipalityA;

    private Municipality $municipalityB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->municipalityA = Municipality::factory()->create(['code' => 'MUN-A']);
        $this->municipalityB = Municipality::factory()->create(['code' => 'MUN-B']);
    }

    public function test_exact_permission_without_legacy_fixed_role_lists_only_own_municipality(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, ['users.view']);
        $local = User::factory()->create([
            'municipality_id' => $this->municipalityA->id,
            'name' => 'Utilizador Município A',
        ]);
        $foreign = User::factory()->create([
            'municipality_id' => $this->municipalityB->id,
            'name' => 'Utilizador Município B',
        ]);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.users.index'))
            ->assertOk()
            ->assertSee($local->name)
            ->assertDontSee($foreign->name);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.users.show', $foreign))
            ->assertForbidden();
    }

    public function test_foreign_team_role_and_access_events_are_not_visible_or_mutable(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, [
            'roles.view',
            'roles.update',
            'teams.view',
            'teams.update',
            'access_audit.view',
        ]);
        $foreignTeam = MunicipalTeam::factory()->create([
            'municipality_id' => $this->municipalityB->id,
            'name' => 'Equipa Município B',
        ]);
        $foreignRole = $this->municipalRole($this->municipalityB, 'perfil_municipio_b');
        $localEvent = AccessChangeEvent::factory()
            ->forMunicipality($this->municipalityA)
            ->create([
                'event_code' => 'municipality_a_event',
                'actor_id' => $actor->id,
                'justification' => 'Evento exclusivo do Município A',
            ]);
        $foreignActor = User::factory()->create([
            'municipality_id' => $this->municipalityB->id,
        ]);
        $foreignEvent = AccessChangeEvent::factory()
            ->forMunicipality($this->municipalityB)
            ->create([
                'event_code' => 'municipality_b_event',
                'actor_id' => $foreignActor->id,
                'justification' => 'Evento exclusivo do Município B',
            ]);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.teams.show', $foreignTeam))
            ->assertForbidden();

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.roles.show', $foreignRole))
            ->assertForbidden();

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.access-audit.index'))
            ->assertOk()
            ->assertSee($localEvent->event_code)
            ->assertDontSee($foreignEvent->event_code);

        $this->assertSame('Equipa Município B', $foreignTeam->refresh()->name);
        $this->assertSame('Perfil Municipio B', $foreignRole->refresh()->label);
    }

    public function test_custom_role_is_created_in_actor_municipality_and_system_role_remains_read_only(): void
    {
        $actor = $this->userWithPermissions($this->municipalityA, [
            'roles.view',
            'roles.create',
            'applications.view',
        ]);
        $permission = Permission::query()->where('name', 'applications.view')->firstOrFail();

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.roles.store'), [
                'label' => 'Consulta municipal isolada',
                'permissions' => [$permission->id],
                'justification' => 'Criar perfil no Município A.',
            ])
            ->assertRedirect();

        $role = Role::query()->where('label', 'Consulta municipal isolada')->sole();
        $this->assertSame($this->municipalityA->id, $role->municipality_id);

        $systemRole = Role::query()->where('name', 'municipal_technician')->firstOrFail();
        $this->assertNull($systemRole->municipality_id);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.roles.show', $systemRole))
            ->assertOk()
            ->assertSee('modo de leitura');
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(Municipality $municipality, array $permissions): User
    {
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
        $role = $this->municipalRole(
            $municipality,
            'scope_test_'.$user->id.'_'.Role::query()->count(),
        );
        $role->permissions()->sync(
            Permission::query()->whereIn('name', $permissions)->pluck('id')->all(),
        );
        $user->roles()->attach($role);

        return $user;
    }

    private function municipalRole(Municipality $municipality, string $name): Role
    {
        return Role::query()->create([
            'municipality_id' => $municipality->id,
            'name' => $name,
            'label' => str($name)->replace('_', ' ')->title()->toString(),
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ]);
    }
}
