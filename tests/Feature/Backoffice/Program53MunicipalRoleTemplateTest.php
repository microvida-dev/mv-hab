<?php

namespace Tests\Feature\Backoffice;

use App\Models\AccessChangeEvent;
use App\Models\Municipality;
use App\Models\MunicipalityFeatureEntitlement;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Program53MunicipalRoleTemplateTest extends TestCase
{
    use RefreshDatabase;

    private Municipality $municipality;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->municipality = Municipality::factory()->create();
    }

    public function test_preview_exposes_matrix_dependencies_mfa_and_sensitive_export_boundary(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route(
                'backoffice.role-templates.create',
                'analista-candidaturas-exportacao',
            ))
            ->assertOk()
            ->assertSee('Analista de candidaturas e exportação')
            ->assertSee('applications.review')
            ->assertSee('applications.export')
            ->assertSee('reports.export_sensitive')
            ->assertSee('MFA obrigatório por permission')
            ->assertSee('A exportação sensível exige autorização adicional separada')
            ->assertSee('Confirmo a aplicação desta matriz exata');

        $this->assertDatabaseHas('access_change_events', [
            'event_code' => 'municipal_role_template_previewed',
            'municipality_id' => $this->municipality->id,
            'actor_id' => $administrator->id,
        ]);
        $this->assertDatabaseCount('municipality_feature_entitlements', 0);
    }

    public function test_template_application_is_exact_idempotent_and_does_not_assign_users_or_entitlements(): void
    {
        $administrator = $this->administrator();
        $template = app(MunicipalRoleTemplateRegistry::class)
            ->resolve('analista-candidaturas-exportacao');
        $payload = [
            'template_key' => $template['key'],
            'confirm_template' => '1',
            'justification' => 'Aplicação municipal controlada do perfil do Programa 53.',
        ];

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.roles.store'), $payload)
            ->assertRedirect();

        $role = Role::query()
            ->where('municipality_id', $this->municipality->id)
            ->where('template_key', $template['key'])
            ->sole();

        $this->assertSame($template['version'], $role->template_version);
        $this->assertSame($template['fingerprint'], $role->template_fingerprint);
        $this->assertTrue($role->isTemplateBased());
        $this->assertEqualsCanonicalizing(
            $template['permissions'],
            $role->permissions()->pluck('name')->all(),
        );
        $this->assertFalse($role->permissions()->where('name', 'reports.export_sensitive')->exists());
        $this->assertFalse($role->users()->exists());
        $this->assertSame(0, MunicipalityFeatureEntitlement::query()->count());

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.roles.store'), $payload)
            ->assertRedirect(route('backoffice.roles.show', $role));

        $this->assertSame(1, Role::query()
            ->where('municipality_id', $this->municipality->id)
            ->where('template_key', $template['key'])
            ->count());
        $this->assertSame(count($template['permissions']), $role->permissions()->count());
        $this->assertSame(1, AccessChangeEvent::query()
            ->where('event_code', 'municipal_role_template_created')
            ->where('role_id', $role->id)
            ->count());
    }

    public function test_drift_is_refused_until_explicit_reconciliation(): void
    {
        $administrator = $this->administrator();
        $template = app(MunicipalRoleTemplateRegistry::class)
            ->resolve('analista-candidaturas-exportacao');
        $role = $this->applyTemplate($administrator, $template['key']);
        $required = Permission::query()->where('name', 'documents.approve')->firstOrFail();
        $foreign = Permission::query()->where('name', 'finance.view')->firstOrFail();
        $role->permissions()->detach($required);
        $role->permissions()->attach($foreign);

        $payload = [
            'template_key' => $template['key'],
            'confirm_template' => '1',
            'justification' => 'Revisão explícita de divergência da matriz municipal.',
        ];

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.roles.store'), $payload)
            ->assertSessionHasErrors('role');

        $this->assertFalse($role->permissions()->where('name', 'documents.approve')->exists());
        $this->assertTrue($role->permissions()->where('name', 'finance.view')->exists());
        $this->assertDatabaseHas('access_change_events', [
            'event_code' => 'municipal_role_template_drift_detected',
            'role_id' => $role->id,
        ]);

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.roles.store'), [
                ...$payload,
                'confirm_reconcile' => '1',
            ])
            ->assertRedirect(route('backoffice.roles.show', $role));

        $this->assertEqualsCanonicalizing(
            $template['permissions'],
            $role->permissions()->pluck('name')->all(),
        );
        $this->assertDatabaseHas('access_change_events', [
            'event_code' => 'municipal_role_template_reconciled',
            'role_id' => $role->id,
        ]);
    }

    public function test_historical_custom_role_is_not_linked_to_template_by_name_or_label(): void
    {
        $administrator = $this->administrator();
        $historical = Role::query()->create([
            'municipality_id' => $this->municipality->id,
            'name' => 'analista_candidaturas_exportacao_historico',
            'label' => 'Analista de candidaturas e exportação',
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ]);

        $created = $this->applyTemplate($administrator, 'analista-candidaturas-exportacao');

        $this->assertNotSame($historical->id, $created->id);
        $this->assertNull($historical->refresh()->template_key);
        $this->assertSame(1, Role::query()
            ->where('municipality_id', $this->municipality->id)
            ->where('template_key', 'analista-candidaturas-exportacao')
            ->count());
    }

    private function administrator(): User
    {
        $administrator = User::factory()->create([
            'municipality_id' => $this->municipality->id,
            'status' => 'active',
        ]);
        $administrator->assignRole('administrator');

        return $administrator;
    }

    private function applyTemplate(User $administrator, string $templateKey): Role
    {
        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.roles.store'), [
                'template_key' => $templateKey,
                'confirm_template' => '1',
                'justification' => 'Aplicação inicial controlada do template municipal.',
            ])
            ->assertRedirect();

        return Role::query()
            ->where('municipality_id', $this->municipality->id)
            ->where('template_key', $templateKey)
            ->sole();
    }
}
