<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Enums\PlatformOperatorStatus;
use App\Models\AuditEvent;
use App\Models\Municipality;
use App\Models\PlatformOperatorAssignment;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use App\Services\Platform\ActorProfileResolver;
use App\Services\Platform\PlatformMunicipalContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Validation\ValidationException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PlatformMunicipalContextServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_operator_selects_active_municipality_without_changing_identity(): void
    {
        $municipality = Municipality::factory()->create();
        $user = $this->platformOperator();
        $rolesBefore = $user->roles()->pluck('roles.id')->all();
        $session = $this->sessionStore('context-main');
        $service = $this->service($session);

        $service->activate(
            $user,
            $municipality,
            'Apoio operacional autorizado ao Município.',
        );

        $this->assertSame(
            $municipality->id,
            $session->get(PlatformMunicipalContextService::SESSION_KEY),
        );
        $this->assertSame(
            $municipality->id,
            $service->effectiveMunicipality($user)?->id,
        );
        $this->assertNull($user->fresh()?->municipality_id);
        $this->assertSame(
            $rolesBefore,
            $user->fresh()?->roles()->pluck('roles.id')->all(),
        );
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'platform_municipal_context_entered',
            'user_id' => $user->id,
            'auditable_type' => $municipality->getMorphClass(),
            'auditable_id' => $municipality->id,
        ]);
    }

    public function test_inactive_municipality_cannot_be_selected(): void
    {
        $municipality = Municipality::factory()->create([
            'active' => false,
        ]);
        $user = $this->platformOperator();
        $service = $this->service($this->sessionStore('context-inactive'));

        $this->expectException(ValidationException::class);

        $service->activate(
            $user,
            $municipality,
            'Apoio operacional autorizado ao Município.',
        );
    }

    public function test_revoked_assignment_invalidates_existing_context(): void
    {
        $municipality = Municipality::factory()->create();
        $user = $this->platformOperator();
        $assignment = PlatformOperatorAssignment::query()
            ->where('user_id', $user->id)
            ->firstOrFail();
        $session = $this->sessionStore('context-revoked');
        $service = $this->service($session);

        $service->activate(
            $user,
            $municipality,
            'Apoio operacional autorizado ao Município.',
        );

        $assignment->forceFill([
            'status' => PlatformOperatorStatus::Revoked,
            'revoked_at' => now(),
            'revoke_justification' => 'Revogação explícita para teste de fronteira.',
        ])->save();

        $this->assertNull($service->currentMunicipality($user));
        $this->assertFalse(
            $session->has(PlatformMunicipalContextService::SESSION_KEY),
        );
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'platform_municipal_context_invalidated',
            'user_id' => $user->id,
        ]);
    }

    public function test_independent_sessions_do_not_share_municipal_context(): void
    {
        $municipality = Municipality::factory()->create();
        $user = $this->platformOperator();
        $sessionA = $this->sessionStore('context-a');
        $sessionB = $this->sessionStore('context-b');
        $serviceA = $this->service($sessionA);
        $serviceB = $this->service($sessionB);

        $serviceA->activate(
            $user,
            $municipality,
            'Apoio operacional autorizado ao Município.',
        );

        $this->assertSame(
            $municipality->id,
            $serviceA->currentMunicipality($user)?->id,
        );
        $this->assertNull($serviceB->currentMunicipality($user));
    }

    public function test_municipal_user_uses_persisted_municipality_without_session_context(): void
    {
        $municipality = Municipality::factory()->create();
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
        $role = Role::query()->create([
            'municipality_id' => $municipality->id,
            'name' => 'municipal_technician',
            'label' => 'Técnico municipal',
            'scope' => 'municipal',
            'is_system' => false,
            'is_active' => true,
        ]);
        $user->assignRole($role);
        $session = $this->sessionStore('context-municipal');

        $this->assertSame(
            $municipality->id,
            $this->service($session)->effectiveMunicipality($user)?->id,
        );
        $this->assertFalse(
            $session->has(PlatformMunicipalContextService::SESSION_KEY),
        );
    }

    public function test_context_change_and_clear_are_audited(): void
    {
        $first = Municipality::factory()->create();
        $second = Municipality::factory()->create();
        $user = $this->platformOperator();
        $session = $this->sessionStore('context-change-clear');
        $service = $this->service($session);

        $service->activate(
            $user,
            $first,
            'Apoio operacional autorizado ao primeiro Município.',
        );
        $service->activate(
            $user,
            $second,
            'Mudança autorizada para apoio ao segundo Município.',
        );

        $changed = AuditEvent::query()
            ->where('event_code', 'platform_municipal_context_changed')
            ->where('user_id', $user->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            $first->id,
            data_get($changed->old_values, 'municipality_id'),
        );
        $this->assertSame(
            $second->id,
            data_get($changed->new_values, 'municipality_id'),
        );

        $service->clear(
            $user,
            'Conclusão do apoio operacional autorizado.',
        );

        $this->assertFalse(
            $session->has(PlatformMunicipalContextService::SESSION_KEY),
        );
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'platform_municipal_context_cleared',
            'user_id' => $user->id,
            'auditable_type' => $second->getMorphClass(),
            'auditable_id' => $second->id,
        ]);
    }

    public function test_context_is_invalidated_when_municipality_becomes_inactive(): void
    {
        $municipality = Municipality::factory()->create();
        $user = $this->platformOperator();
        $session = $this->sessionStore('context-municipality-disabled');
        $service = $this->service($session);

        $service->activate(
            $user,
            $municipality,
            'Apoio operacional autorizado ao Município.',
        );

        $municipality->forceFill(['active' => false])->save();

        $this->assertNull($service->currentMunicipality($user));
        $this->assertFalse(
            $session->has(PlatformMunicipalContextService::SESSION_KEY),
        );
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'platform_municipal_context_invalidated',
            'user_id' => $user->id,
        ]);
    }

    public function test_unclassified_user_with_municipality_has_no_operational_context(): void
    {
        $municipality = Municipality::factory()->create();
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);

        $this->assertNull(
            $this->service(
                $this->sessionStore('context-unclassified-municipal'),
            )->effectiveMunicipality($user),
        );
    }

    public function test_deactivated_platform_operator_invalidates_existing_context(): void
    {
        $municipality = Municipality::factory()->create();
        $user = $this->platformOperator();
        $session = $this->sessionStore('context-deactivated');
        $service = $this->service($session);

        $service->activate(
            $user,
            $municipality,
            'Apoio operacional autorizado ao Município.',
        );

        $user->forceFill(['deactivated_at' => now()])->save();

        $this->assertNull($service->currentMunicipality($user));
        $this->assertFalse(
            $session->has(PlatformMunicipalContextService::SESSION_KEY),
        );
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'platform_municipal_context_invalidated',
            'user_id' => $user->id,
        ]);
    }

    public function test_context_is_not_activated_when_audit_fails(): void
    {
        $municipality = Municipality::factory()->create();
        $user = $this->platformOperator();
        $session = $this->sessionStore('context-audit-enter-failure');
        $audit = Mockery::mock(AuditTrailService::class);
        $audit->shouldReceive('record')
            ->once()
            ->andThrow(new RuntimeException('Audit storage unavailable.'));

        try {
            $this->service($session, $audit)->activate(
                $user,
                $municipality,
                'Apoio operacional autorizado ao Município.',
            );

            $this->fail('A falha de auditoria deveria interromper a ativação.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Audit storage unavailable.',
                $exception->getMessage(),
            );
        }

        $this->assertFalse(
            $session->has(PlatformMunicipalContextService::SESSION_KEY),
        );
    }

    public function test_context_is_not_cleared_when_audit_fails(): void
    {
        $municipality = Municipality::factory()->create();
        $user = $this->platformOperator();
        $session = $this->sessionStore('context-audit-clear-failure');

        $this->service($session)->activate(
            $user,
            $municipality,
            'Apoio operacional autorizado ao Município.',
        );

        $audit = Mockery::mock(AuditTrailService::class);
        $audit->shouldReceive('record')
            ->once()
            ->andThrow(new RuntimeException('Audit storage unavailable.'));

        try {
            $this->service($session, $audit)->clear(
                $user,
                'Conclusão do apoio operacional autorizado.',
            );

            $this->fail('A falha de auditoria deveria interromper a saída.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Audit storage unavailable.',
                $exception->getMessage(),
            );
        }

        $this->assertSame(
            $municipality->id,
            $session->get(PlatformMunicipalContextService::SESSION_KEY),
        );
    }

    private function platformOperator(): User
    {
        $user = User::factory()->create([
            'municipality_id' => null,
            'status' => 'active',
        ]);

        PlatformOperatorAssignment::factory()->for($user)->create();

        return $user;
    }

    private function sessionStore(string $name): Store
    {
        $store = new Store(
            $name,
            new ArraySessionHandler(120),
        );
        $store->start();

        return $store;
    }

    private function service(
        Store $session,
        ?AuditTrailService $audit = null,
    ): PlatformMunicipalContextService {
        return new PlatformMunicipalContextService(
            app(ActorProfileResolver::class),
            $audit ?? app(AuditTrailService::class),
            $session,
            Request::create('/__tests/platform-context'),
        );
    }
}
