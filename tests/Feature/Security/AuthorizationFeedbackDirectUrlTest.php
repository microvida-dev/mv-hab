<?php

namespace Tests\Feature\Security;

use App\Enums\AccessDenialReason;
use App\Enums\FeatureKey;
use App\Exceptions\AccessDeniedException;
use App\Models\Municipality;
use App\Models\User;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthorizationFeedbackDirectUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);

        Route::middleware(['web', 'auth'])
            ->get('/__tests/access-feedback/scope', function (): never {
                throw new AccessDeniedException(
                    AccessDenialReason::RecordOutOfScope,
                    ['scope' => 'municipal'],
                );
            })
            ->name('tests.access-feedback.scope');

        Route::middleware(['web', 'auth'])
            ->get('/__tests/access-feedback/generic', function (): never {
                throw new AuthorizationException('PolicyInternalName::view');
            })
            ->name('tests.access-feedback.generic');

        Route::middleware(['web', 'auth'])
            ->get('/__tests/access-feedback/hidden', function (): never {
                abort(404);
            })
            ->name('tests.access-feedback.hidden');

        Route::middleware(['web', 'auth', 'role:administrator'])
            ->get('/backoffice/__tests/access-feedback/candidate', fn (): string => 'allowed')
            ->name('backoffice.tests.access-feedback.candidate');

        Route::middleware(['web', 'auth', 'active.backoffice'])
            ->get('/backoffice/__tests/access-feedback/inactive', fn (): string => 'allowed')
            ->name('backoffice.tests.access-feedback.inactive');

        Route::middleware([
            'web',
            'auth',
            'municipality.feature:'.FeatureKey::ApplicationIntake->value,
        ])->get('/backoffice/__tests/access-feedback/feature', fn (): string => 'allowed')
            ->name('backoffice.tests.access-feedback.feature');

        Route::middleware(['web', 'auth', 'permission:users.update'])
            ->post('/backoffice/__tests/access-feedback/auditor-mutation', function (Request $request): string {
                $user = $request->user();

                if ($user instanceof User) {
                    $user->update(['name' => 'Alteração indevida']);
                }

                return 'mutated';
            })
            ->name('backoffice.tests.access-feedback.auditor-mutation');

        Route::middleware(['web', 'auth'])
            ->get('/__tests/access-feedback/auditor-origin', fn (): string => 'safe origin')
            ->name('tests.access-feedback.auditor-origin');

        Route::getRoutes()->refreshNameLookups();
    }

    public function test_explicit_scope_denial_is_safe_and_hidden_routes_remain_404(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->get(route('tests.access-feedback.scope'))
            ->assertForbidden()
            ->assertSeeText('Este recurso não está disponível no seu âmbito de acesso.')
            ->assertDontSee('scope')
            ->assertDontSee('municipality_id');

        $this->get(route('tests.access-feedback.hidden'))
            ->assertNotFound()
            ->assertDontSee('Não tem permissão para realizar esta ação.');
    }

    public function test_generic_authorization_exception_uses_missing_permission_fallback(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->get(route('tests.access-feedback.generic'))
            ->assertForbidden()
            ->assertSeeText('Não tem permissão para realizar esta ação.')
            ->assertDontSee('PolicyInternalName');
    }

    public function test_candidate_inactive_account_and_disabled_feature_keep_distinct_safe_feedback(): void
    {
        $candidate = User::factory()->create(['status' => 'active']);
        $candidate->assignRole('candidate');

        $this->actingAs($candidate)
            ->get(route('backoffice.tests.access-feedback.candidate'))
            ->assertForbidden()
            ->assertSeeText('Esta área está reservada à operação municipal.')
            ->assertDontSee('administrator');

        $inactive = User::factory()->create(['status' => 'inactive']);
        $inactive->assignRole('support_agent');

        $this->actingAs($inactive)
            ->get(route('backoffice.tests.access-feedback.inactive'))
            ->assertForbidden()
            ->assertSeeText('A sua conta não está autorizada a aceder ao backoffice.');

        $municipality = Municipality::factory()->create();
        $municipalUser = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);

        $this->actingAs($municipalUser)
            ->get(route('backoffice.tests.access-feedback.feature'))
            ->assertForbidden()
            ->assertSeeText('Esta funcionalidade não está disponível para o Município atual.')
            ->assertDontSee(FeatureKey::ApplicationIntake->value);
    }

    public function test_mfa_keeps_the_existing_challenge_instead_of_generic_feedback(): void
    {
        $administrator = User::factory()->create([
            'status' => 'active',
            'mfa_required' => true,
        ]);
        $administrator->assignRole('administrator');

        $this->actingAs($administrator)
            ->get(route('backoffice.security.dashboard'))
            ->assertRedirect(route('backoffice.security.mfa.index'))
            ->assertSessionHas(
                'warning',
                'Configure ou confirme MFA para aceder a rotas sensíveis.',
            );
    }

    public function test_auditor_remains_read_only_and_receives_safe_mutation_feedback(): void
    {
        $auditor = User::factory()->create([
            'name' => 'Auditor preservado',
            'status' => 'active',
        ]);
        $auditor->assignRole('auditor');

        $this->actingAs($auditor)
            ->from(route('tests.access-feedback.auditor-origin'))
            ->post(route('backoffice.tests.access-feedback.auditor-mutation'))
            ->assertStatus(303)
            ->assertRedirect(route('tests.access-feedback.auditor-origin'))
            ->assertSessionHas(
                'warning',
                'Não tem permissão para realizar esta ação. A operação não foi executada.',
            );

        $this->assertSame('Auditor preservado', $auditor->refresh()->name);
    }
}
