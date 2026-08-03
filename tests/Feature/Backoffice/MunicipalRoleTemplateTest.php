<?php

namespace Tests\Feature\Backoffice;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\MunicipalRoleTemplateRegistry;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MunicipalRoleTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_template_routes_use_permissions_and_backoffice_guards(): void
    {
        foreach ([
            'backoffice.role-templates.index' => 'permission:roles.view',
            'backoffice.role-templates.create' => 'permission:roles.create',
        ] as $routeName => $permission) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route);
            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );

            $this->assertContains('auth', $middleware);
            $this->assertContains('active.backoffice', $middleware);
            $this->assertContains('mfa.backoffice', $middleware);
            $this->assertContains('log.backoffice', $middleware);
            $this->assertContains($permission, $middleware);
            $this->assertFalse(collect($middleware)->contains(
                fn (string $item): bool => str_starts_with($item, 'role:'),
            ));
        }
    }

    public function test_templates_are_reviewable_and_do_not_create_or_assign_roles_automatically(): void
    {
        $administrator = $this->administrator();
        $rolesBefore = Role::query()->count();
        $applicationCreate = Permission::query()->where('name', 'applications.create')->firstOrFail();
        $applicationExport = Permission::query()->where('name', 'applications.export')->firstOrFail();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.role-templates.index'))
            ->assertOk()
            ->assertSee('Operador de recolha')
            ->assertSee('Analista de candidaturas')
            ->assertSee('Exportador de candidaturas');

        $response = $this->get(route('backoffice.role-templates.create', 'operador-recolha'));

        $response->assertOk()
            ->assertSee('Confirme a matriz oficial')
            ->assertSee('applications.create')
            ->assertSee('documents.create');

        $content = $response->getContent();
        if (! is_string($content)) {
            $this->fail('A resposta do template municipal não devolveu conteúdo HTML.');
        }

        $this->assertMatchesRegularExpression(
            '/<input(?=[^>]*value="'.$applicationCreate->id.'")(?=[^>]*checked)[^>]*>/',
            $content,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<input(?=[^>]*value="'.$applicationExport->id.'")(?=[^>]*checked)[^>]*>/',
            $content,
        );

        $this->assertSame($rolesBefore, Role::query()->count());
        $this->assertSame(1, $administrator->roles()->count());
    }

    public function test_template_uses_only_existing_exact_permissions_and_creates_a_custom_role_after_review(): void
    {
        $administrator = $this->administrator();
        $template = app(MunicipalRoleTemplateRegistry::class)->resolve('exportador-candidaturas');

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.roles.store'), [
                'label' => $template['label'],
                'description' => $template['description'],
                'permissions' => $template['permission_ids'],
                'justification' => 'Criação após revisão integral do modelo.',
            ])
            ->assertRedirect();

        $role = Role::query()->where('label', 'Exportador de candidaturas')->firstOrFail();
        $this->assertTrue($role->isMunicipalCustom());
        $this->assertEqualsCanonicalizing($template['permissions'], $role->permissions()->pluck('name')->all());
        $this->assertFalse($role->users()->exists());
    }

    public function test_template_fails_clearly_when_a_required_permission_is_missing(): void
    {
        $administrator = $this->administrator();
        Permission::query()->where('name', 'documents.create')->delete();

        $this->actingAs($administrator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.role-templates.create', 'operador-recolha'))
            ->assertRedirect(route('backoffice.role-templates.index'))
            ->assertSessionHasErrors('template');
    }

    private function administrator(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('administrator');

        return $user;
    }
}
