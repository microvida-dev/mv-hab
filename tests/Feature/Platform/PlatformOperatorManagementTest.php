<?php

namespace Tests\Feature\Platform;

use App\Models\PlatformOperatorAssignment;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreatesPlatformOperatorFixtures;
use Tests\TestCase;

class PlatformOperatorManagementTest extends TestCase
{
    use CreatesPlatformOperatorFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
    }

    public function test_routes_are_permission_first_and_keep_all_backoffice_guards(): void
    {
        $expected = [
            'backoffice.platform.operators.index' => 'permission:platform_operators.view',
            'backoffice.platform.operators.store' => 'permission:platform_operators.manage',
            'backoffice.platform.operators.audit' => 'permission:platform_operators.audit',
            'backoffice.platform.operators.show' => 'permission:platform_operators.view',
            'backoffice.platform.operators.revoke' => 'permission:platform_operators.manage',
        ];

        foreach ($expected as $routeName => $permission) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route, $routeName);
            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );

            $this->assertContains('auth', $middleware, $routeName);
            $this->assertContains('active.backoffice', $middleware, $routeName);
            $this->assertContains('mfa.backoffice', $middleware, $routeName);
            $this->assertContains('log.backoffice', $middleware, $routeName);
            $this->assertContains($permission, $middleware, $routeName);
            $this->assertFalse(
                collect($middleware)->contains(
                    fn (string $item): bool => str_starts_with($item, 'role:'),
                ),
                $routeName,
            );
        }
    }

    public function test_operator_can_list_grant_view_and_revoke_without_changing_roles(): void
    {
        $actor = $this->platformUser([
            'platform_operators.view',
            'platform_operators.manage',
            'platform_operators.audit',
        ]);
        $target = $this->platformUser(['platform_operators.view'], assigned: false);
        $targetRoles = $target->roles()->pluck('roles.id')->all();

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.platform.operators.index'))
            ->assertOk()
            ->assertSee('Operadores de plataforma');

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.platform.operators.store'), [
                'user_id' => $target->id,
                'justification' => 'Concessão funcional aprovada para a conta dedicada.',
            ])
            ->assertRedirect();

        $assignment = PlatformOperatorAssignment::query()
            ->where('user_id', $target->id)
            ->sole();

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.platform.operators.show', $assignment))
            ->assertOk()
            ->assertSee($target->name);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.platform.operators.revoke', $assignment), [
                'justification' => 'Revogação aprovada após cessação da função global.',
            ])
            ->assertRedirect();

        $this->assertFalse($assignment->refresh()->isActive());
        $this->assertSame($targetRoles, $target->roles()->pluck('roles.id')->all());
    }

    public function test_candidate_and_unassigned_platform_administrator_fail_closed(): void
    {
        $candidate = User::factory()->create(['status' => 'active']);
        $candidate->assignRole('candidate');
        $unassigned = $this->platformUser(['platform_operators.view'], assigned: false);

        $this->actingAs($candidate)
            ->get(route('backoffice.platform.operators.index'))
            ->assertForbidden();

        $this->actingAs($unassigned)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.platform.operators.index'))
            ->assertForbidden();
    }
}
