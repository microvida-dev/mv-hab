<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Enums\PlatformOperatorStatus;
use App\Models\AuditEvent;
use App\Models\Municipality;
use App\Models\MunicipalityFeatureEntitlement;
use App\Models\Permission;
use App\Models\PlatformOperatorAssignment;
use App\Models\User;
use App\Services\Platform\PlatformMunicipalContextService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreatesPlatformOperatorFixtures;
use Tests\TestCase;

class PlatformMunicipalContextRoutesTest extends TestCase
{
    use CreatesPlatformOperatorFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_routes_are_permission_first_and_keep_platform_guards(): void
    {
        $this->assertTrue(
            Permission::query()
                ->where('name', 'municipalities.view')
                ->exists(),
        );
        $this->assertFalse(
            Permission::query()
                ->where('module', 'platform_context')
                ->exists(),
        );

        foreach ([
            'backoffice.platform.municipal-context.index',
            'backoffice.platform.municipal-context.store',
            'backoffice.platform.municipal-context.destroy',
        ] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, $routeName);

            $middleware = app('router')->resolveMiddleware(
                $route->gatherMiddleware(),
                $route->excludedMiddleware(),
            );

            foreach ([
                'auth',
                'active.backoffice',
                'mfa.backoffice',
                'log.backoffice',
                'platform.operator',
                'permission:municipalities.view',
            ] as $guard) {
                $this->assertContains($guard, $middleware, $routeName);
            }

            $this->assertFalse(
                collect($middleware)->contains(
                    fn (string $item): bool => str_starts_with($item, 'role:'),
                ),
                $routeName,
            );
        }
    }

    public function test_selector_is_paginated_searchable_and_does_not_expose_contact_email(): void
    {
        $actor = $this->platformUser(['municipalities.view']);

        Municipality::factory()->count(21)->sequence(
            fn (Sequence $sequence): array => [
                'name' => sprintf('Município %02d', $sequence->index + 1),
                'code' => sprintf('M%02d', $sequence->index + 1),
                'tax_number' => sprintf('500000%03d', $sequence->index + 1),
                'contact_email' => sprintf('private-%02d@example.test', $sequence->index + 1),
                'active' => true,
            ],
        )->create();

        $target = Municipality::factory()->create([
            'name' => 'Município Pesquisa Especial',
            'code' => 'ALC-SEARCH',
            'tax_number' => '509999999',
            'contact_email' => 'segredo-institucional@example.test',
            'active' => true,
        ]);
        Municipality::factory()->create([
            'name' => 'Município Inativo',
            'code' => 'INACTIVE-01',
            'active' => false,
        ]);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.platform.municipal-context.index'))
            ->assertOk()
            ->assertViewHas(
                'municipalities',
                fn (LengthAwarePaginator $paginator): bool => $paginator->perPage() === 20
                    && $paginator->total() === 23,
            )
            ->assertSee('page=2', false)
            ->assertDontSee('segredo-institucional@example.test');

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.platform.municipal-context.index', [
                'q' => 'ALC-SEARCH',
                'status' => 'active',
            ]))
            ->assertOk()
            ->assertSee($target->name)
            ->assertSee($target->tax_number)
            ->assertDontSee('Município Inativo')
            ->assertDontSee('segredo-institucional@example.test');

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.platform.municipal-context.index', [
                'status' => 'inactive',
            ]))
            ->assertOk()
            ->assertSee('Município Inativo')
            ->assertSee('Indisponível');
    }

    public function test_platform_operator_can_enter_change_and_clear_context_with_audit(): void
    {
        $actor = $this->platformUser(['municipalities.view']);
        $first = Municipality::factory()->create([
            'name' => 'Município Primeiro',
            'active' => true,
        ]);
        $second = Municipality::factory()->create([
            'name' => 'Município Segundo',
            'active' => true,
        ]);
        $roleIds = $actor->roles()->pluck('roles.id')->all();
        $permissionCount = $actor->roles()
            ->withCount('permissions')
            ->get()
            ->sum('permissions_count');
        $entitlementCount = MunicipalityFeatureEntitlement::query()->count();

        $enteredJustification = 'Apoio autorizado para validação do primeiro Município.';

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.platform.municipal-context.store'), [
                'municipality_id' => $first->id,
                'justification' => $enteredJustification,
                'confirm' => '1',
            ])
            ->assertRedirect(route('backoffice.platform.municipal-context.index'))
            ->assertSessionHas(
                PlatformMunicipalContextService::SESSION_KEY,
                $first->id,
            );

        $entered = AuditEvent::query()
            ->where('event_code', 'platform_municipal_context_entered')
            ->where('user_id', $actor->id)
            ->sole();

        $this->assertSame(
            $first->id,
            data_get($entered->new_values, 'municipality_id'),
        );
        $this->assertSame(
            $enteredJustification,
            data_get($entered->metadata, 'justification'),
        );
        $this->assertArrayNotHasKey('contact_email', $entered->metadata ?? []);
        $this->assertArrayNotHasKey('tax_number', $entered->metadata ?? []);

        $changedJustification = 'Mudança autorizada para apoio ao segundo Município.';

        $this->actingAs($actor)
            ->withSession([
                'mfa.verified_at' => now(),
                PlatformMunicipalContextService::SESSION_KEY => $first->id,
            ])
            ->post(route('backoffice.platform.municipal-context.store'), [
                'municipality_id' => $second->id,
                'justification' => $changedJustification,
                'confirm' => 'yes',
            ])
            ->assertRedirect(route('backoffice.platform.municipal-context.index'))
            ->assertSessionHas(
                PlatformMunicipalContextService::SESSION_KEY,
                $second->id,
            );

        $changed = AuditEvent::query()
            ->where('event_code', 'platform_municipal_context_changed')
            ->where('user_id', $actor->id)
            ->sole();

        $this->assertSame(
            $first->id,
            data_get($changed->old_values, 'municipality_id'),
        );
        $this->assertSame(
            $second->id,
            data_get($changed->new_values, 'municipality_id'),
        );

        $this->actingAs($actor)
            ->withSession([
                'mfa.verified_at' => now(),
                PlatformMunicipalContextService::SESSION_KEY => $second->id,
            ])
            ->delete(route('backoffice.platform.municipal-context.destroy'), [
                'justification' => 'Conclusão do apoio operacional autorizado.',
                'confirm' => 'on',
            ])
            ->assertRedirect(route('backoffice.platform.municipal-context.index'))
            ->assertSessionMissing(
                PlatformMunicipalContextService::SESSION_KEY,
            );

        $cleared = AuditEvent::query()
            ->where('event_code', 'platform_municipal_context_cleared')
            ->where('user_id', $actor->id)
            ->sole();

        $this->assertSame(
            $second->id,
            data_get($cleared->old_values, 'municipality_id'),
        );
        $this->assertNull(
            data_get($cleared->new_values, 'municipality_id'),
        );

        $actor->refresh();
        $this->assertNull($actor->municipality_id);
        $this->assertSame($roleIds, $actor->roles()->pluck('roles.id')->all());
        $this->assertSame(
            $permissionCount,
            $actor->roles()
                ->withCount('permissions')
                ->get()
                ->sum('permissions_count'),
        );
        $this->assertSame(
            $entitlementCount,
            MunicipalityFeatureEntitlement::query()->count(),
        );
    }

    public function test_activation_requires_active_municipality_confirmation_and_bounded_plain_text_justification(): void
    {
        $actor = $this->platformUser(['municipalities.view']);
        $active = Municipality::factory()->create(['active' => true]);
        $inactive = Municipality::factory()->create(['active' => false]);
        $route = route('backoffice.platform.municipal-context.store');

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->from(route('backoffice.platform.municipal-context.index'))
            ->post($route, [
                'municipality_id' => $inactive->id,
                'justification' => 'Apoio operacional devidamente autorizado.',
                'confirm' => '1',
            ])
            ->assertRedirect(route('backoffice.platform.municipal-context.index'))
            ->assertSessionHasErrors('municipality_id')
            ->assertSessionMissing(PlatformMunicipalContextService::SESSION_KEY);

        $this->actingAs($actor)
            ->withSession(['mfa.verified_at' => now()])
            ->from(route('backoffice.platform.municipal-context.index'))
            ->post($route, [
                'municipality_id' => $active->id,
                'justification' => 'Apoio operacional devidamente autorizado.',
            ])
            ->assertSessionHasErrors('confirm')
            ->assertSessionMissing(PlatformMunicipalContextService::SESSION_KEY);

        foreach ([
            'curta',
            '<strong>Apoio operacional autorizado.</strong>',
            str_repeat('a', 501),
        ] as $invalidJustification) {
            $this->actingAs($actor)
                ->withSession(['mfa.verified_at' => now()])
                ->from(route('backoffice.platform.municipal-context.index'))
                ->post($route, [
                    'municipality_id' => $active->id,
                    'justification' => $invalidJustification,
                    'confirm' => '1',
                ])
                ->assertSessionHasErrors('justification')
                ->assertSessionMissing(
                    PlatformMunicipalContextService::SESSION_KEY,
                );
        }

        $this->assertDatabaseMissing('audit_events', [
            'event_code' => 'platform_municipal_context_entered',
            'user_id' => $actor->id,
        ]);
    }

    public function test_guest_mfa_candidate_municipal_unassigned_and_permission_boundaries_fail_closed(): void
    {
        $route = route('backoffice.platform.municipal-context.index');

        $this->get($route)->assertRedirect(route('login'));

        $withoutMfa = $this->platformUser(['municipalities.view']);
        $this->actingAs($withoutMfa)
            ->get($route)
            ->assertRedirect(route('backoffice.security.mfa.index'));

        $candidate = User::factory()->create(['status' => 'active']);
        $candidate->assignRole('candidate');
        $this->actingAs($candidate)
            ->get($route)
            ->assertForbidden();

        $municipality = Municipality::factory()->create();
        $municipal = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
        $municipal->assignRole('municipal_technician');
        $this->actingAs($municipal)
            ->withSession(['mfa.verified_at' => now()])
            ->get($route)
            ->assertForbidden();

        $unassigned = $this->platformUser(
            ['municipalities.view'],
            assigned: false,
        );
        $this->actingAs($unassigned)
            ->withSession(['mfa.verified_at' => now()])
            ->get($route)
            ->assertForbidden();

        $withoutPermission = $this->platformUser(['dashboard.view']);
        $this->actingAs($withoutPermission)
            ->withSession(['mfa.verified_at' => now()])
            ->get($route)
            ->assertForbidden();
    }

    public function test_revoked_assignment_invalidates_stale_context_before_denial(): void
    {
        $actor = $this->platformUser(['municipalities.view']);
        $municipality = Municipality::factory()->create(['active' => true]);
        $assignment = PlatformOperatorAssignment::query()
            ->where('user_id', $actor->id)
            ->sole();

        $assignment->forceFill([
            'status' => PlatformOperatorStatus::Revoked,
            'revoked_by' => $actor->id,
            'revoked_at' => now(),
            'revoke_justification' => 'Revogação autorizada para o teste de invalidação.',
        ])->save();

        $this->actingAs($actor)
            ->withSession([
                'mfa.verified_at' => now(),
                PlatformMunicipalContextService::SESSION_KEY => $municipality->id,
            ])
            ->get(route('backoffice.platform.municipal-context.index'))
            ->assertForbidden()
            ->assertSessionMissing(
                PlatformMunicipalContextService::SESSION_KEY,
            );

        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'platform_municipal_context_invalidated',
            'user_id' => $actor->id,
        ]);
    }

    public function test_platform_dashboard_links_to_selector_only_with_exact_permission(): void
    {
        $authorized = $this->platformUser([
            'dashboard.view',
            'municipalities.view',
        ]);
        $withoutPermission = $this->platformUser(['dashboard.view']);

        $this->actingAs($authorized)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Selecionar Município')
            ->assertSee(
                route('backoffice.platform.municipal-context.index'),
                false,
            );

        $this->actingAs($withoutPermission)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Selecionar Município');
    }
}
