<?php

namespace Tests\Feature\Security;

use App\Enums\AccessDenialReason;
use App\Exceptions\AccessDeniedException;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Security\AuthorizationDenialAuditService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class AuthorizationDenialAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(SystemAccessSeeder::class);

        Route::middleware(['web', 'auth', 'role:administrator'])
            ->get('/backoffice/__tests/access-feedback/audit-candidate', fn (): string => 'allowed')
            ->name('backoffice.tests.access-feedback.audit-candidate');

        Route::middleware(['web', 'auth', 'permission:users.update'])
            ->post('/backoffice/__tests/access-feedback/audit-mutation', fn (): string => 'mutated')
            ->name('backoffice.tests.access-feedback.audit-mutation');

        Route::middleware(['web', 'auth', 'permission:users.view'])
            ->get('/backoffice/__tests/access-feedback/audit-generic', fn (): string => 'allowed')
            ->name('backoffice.tests.access-feedback.audit-generic');

        Route::middleware(['web', 'auth'])
            ->get('/backoffice/__tests/access-feedback/audit-scope', function (): never {
                throw new AccessDeniedException(AccessDenialReason::RecordOutOfScope);
            })
            ->name('backoffice.tests.access-feedback.audit-scope');

        Route::getRoutes()->refreshNameLookups();
    }

    public function test_relevant_denial_is_minimized_correlated_and_deduplicated(): void
    {
        $candidate = User::factory()->create(['status' => 'active']);
        $candidate->assignRole('candidate');

        $first = $this->actingAs($candidate)
            ->get(route('backoffice.tests.access-feedback.audit-candidate'))
            ->assertForbidden();
        $this->get(route('backoffice.tests.access-feedback.audit-candidate'))
            ->assertForbidden();

        $logs = AuditLog::query()
            ->where('event', 'authorization_denied')
            ->get();

        $this->assertCount(1, $logs);

        $metadata = $logs->sole()->metadata;
        $this->assertSame($candidate->id, data_get($metadata, 'actor_id'));
        $this->assertSame(
            'backoffice.tests.access-feedback.audit-candidate',
            data_get($metadata, 'route_name'),
        );
        $this->assertSame('GET', data_get($metadata, 'http_method'));
        $this->assertSame(
            AccessDenialReason::CandidateBackofficeBoundary->value,
            data_get($metadata, 'denial_reason'),
        );
        $this->assertSame(
            $first->headers->get('X-Request-ID'),
            data_get($metadata, 'request_id'),
        );
        $this->assertNull(data_get($metadata, 'payload'));
        $this->assertNull(data_get($metadata, 'permission'));
    }

    public function test_mutations_and_explicit_scope_denials_are_audited_but_generic_get_is_not(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->post(route('backoffice.tests.access-feedback.audit-mutation'))
            ->assertForbidden();

        $this->get(route('backoffice.tests.access-feedback.audit-generic'))
            ->assertForbidden();

        $this->get(route('backoffice.tests.access-feedback.audit-scope'))
            ->assertForbidden();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'authorization_denied',
            'action' => 'deny',
        ]);
        $this->assertSame(
            [
                AccessDenialReason::MissingPermission->value,
                AccessDenialReason::RecordOutOfScope->value,
            ],
            AuditLog::query()
                ->where('event', 'authorization_denied')
                ->orderBy('id')
                ->get()
                ->map(fn (AuditLog $log): mixed => data_get($log->metadata, 'denial_reason'))
                ->all(),
        );
    }

    public function test_audit_failure_does_not_turn_denial_into_500(): void
    {
        $this->mock(
            AuthorizationDenialAuditService::class,
            function ($mock): void {
                $mock->shouldReceive('record')
                    ->once()
                    ->andThrow(new RuntimeException('audit database unavailable'));
            },
        );

        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->get(route('backoffice.tests.access-feedback.audit-scope'))
            ->assertForbidden()
            ->assertSeeText('Este recurso não está disponível no seu âmbito de acesso.');
    }
}
