<?php

namespace Tests\Feature;

use App\Enums\AccessLogType;
use App\Enums\AuditEventCategory;
use App\Enums\AuditEventSeverity;
use App\Enums\RetentionExecutionStatus;
use App\Enums\SecurityChecklistStatus;
use App\Http\Middleware\EnsureBackofficeMfaVerified;
use App\Models\DataExportPackage;
use App\Models\DataSubjectRequest;
use App\Models\RetentionPolicy;
use App\Models\SecurityAlertRule;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use App\Services\Rgpd\AnonymizationService;
use App\Services\Rgpd\RetentionExecutionService;
use App\Services\Security\MfaDeviceService;
use App\Services\Security\PermissionReviewService;
use App\Services\Security\PreProductionSecurityChecklistService;
use Database\Seeders\SecurityAlertRuleSeeder;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class Sprint18RgpdSecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
    }

    public function test_backoffice_security_requires_mfa_and_candidate_privacy_is_accessible(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)
            ->get(route('backoffice.security.dashboard'))
            ->assertRedirect(route('backoffice.security.mfa.index'));

        $candidate = $this->userWithRole('candidate');

        $this->actingAs($candidate)
            ->get(route('candidate.privacy.index'))
            ->assertOk();
    }

    public function test_mfa_secret_is_encrypted_and_recovery_codes_are_single_use(): void
    {
        $administrator = $this->userWithRole('administrator');

        /** @var MfaDeviceService $service */
        $service = app(MfaDeviceService::class);

        $device = $service->createTotpDevice($administrator);

        // O cast do modelo devolve o segredo desencriptado.
        $decryptedSecret = $device->secret_encrypted;

        // Valor efetivamente armazenado na base de dados.
        $encryptedSecret = DB::table('mfa_devices')
            ->whereKey($device->id)
            ->value('secret_encrypted');

        $this->assertNotSame($decryptedSecret, $encryptedSecret);

        $this->assertTrue(
            $service->confirm(
                $device,
                $service->totp($decryptedSecret),
                $administrator,
            )
        );

        $codes = $service->regenerateRecoveryCodes($administrator, 2);

        $this->assertDatabaseMissing('mfa_recovery_codes', [
            'code_hash' => $codes[0],
        ]);

        $this->assertTrue(
            $service->useRecoveryCode($administrator, $codes[0])
        );

        $this->assertFalse(
            $service->useRecoveryCode($administrator, $codes[0])
        );

        $this->assertFalse(
            $service->useRecoveryCode($administrator, 'INVALID-CODE')
        );

        $this->assertFalse(
            $service->verifyTotp($decryptedSecret, "123\n456")
        );
    }

    public function test_failed_login_creates_access_log_and_security_alert(): void
    {
        $this->seed(SecurityAlertRuleSeeder::class);

        SecurityAlertRule::query()
            ->where('code', 'multiple_failed_logins')
            ->update([
                'threshold' => 1,
            ]);

        $user = User::factory()->create([
            'password' => 'valid-secret',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertDatabaseHas('access_logs', [
            'access_type' => AccessLogType::FailedLogin->value,
        ]);

        $this->assertDatabaseHas('security_alerts', [
            'title' => 'Múltiplas falhas de login',
        ]);

        $this->assertGuest();
    }

    public function test_audit_events_are_masked_and_append_only(): void
    {
        /** @var AuditTrailService $auditTrail */
        $auditTrail = app(AuditTrailService::class);

        $event = $auditTrail->record(
            'test.sensitive_update',
            category: AuditEventCategory::Security,
            severity: AuditEventSeverity::Warning,
            oldValues: [
                'password' => 'secret',
                'nif' => '123456789',
            ],
            newValues: [
                'token' => 'abc123',
                'name' => 'Demo',
            ],
        );

        $this->assertSame('[masked]', $event->old_values['password']);
        $this->assertSame('[masked]', $event->old_values['nif']);
        $this->assertSame('[masked]', $event->new_values['token']);

        $this->assertFalse(
            $event->forceFill([
                'event_code' => 'changed',
            ])->save()
        );

        $this->assertSame(
            'test.sensitive_update',
            $event->fresh()->event_code,
        );
    }

    public function test_candidate_can_create_own_rgpd_request_and_export_but_cannot_access_other_candidate_request(): void
    {
        Storage::fake('local');

        $candidate = $this->userWithRole('candidate');
        $other = $this->userWithRole('candidate');

        $this->actingAs($candidate)
            ->post(route('candidate.privacy.requests.store'), [
                'request_type' => 'access',
                'description' => 'Pretendo consultar os meus dados pessoais tratados pela plataforma.',
            ])
            ->assertRedirect();

        $requestRecord = DataSubjectRequest::query()
            ->where('user_id', $candidate->id)
            ->firstOrFail();

        $this->actingAs($other)
            ->get(route('candidate.privacy.requests.show', $requestRecord))
            ->assertForbidden();

        $this->actingAs($candidate)
            ->post(
                route(
                    'candidate.privacy.requests.export',
                    $requestRecord,
                )
            )
            ->assertRedirect();

        $package = DataExportPackage::query()->firstOrFail();

        Storage::disk('local')->assertExists($package->storage_path);
        $payload = Storage::disk('local')->get($package->storage_path);

        $this->assertSame(hash('sha256', $payload), $package->checksum);
        $this->assertSame($candidate->id, data_get(json_decode($payload, true), 'profile.id'));

        $this->assertStringNotContainsString(
            $candidate->email,
            $package->storage_path,
        );

        $this->assertStringNotContainsString(
            $candidate->name,
            $package->storage_path,
        );

        $this->actingAs($candidate)
            ->get(route('candidate.privacy.exports.download', $package))
            ->assertOk();

        $this->assertDatabaseHas('access_logs', [
            'user_id' => $candidate->id,
            'access_type' => AccessLogType::ExportDownload->value,
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'data_export.generated',
            'subject_user_id' => $candidate->id,
        ]);
    }

    public function test_backoffice_can_create_and_assign_rgpd_request_to_existing_user(): void
    {
        $this->withoutMiddleware(EnsureBackofficeMfaVerified::class);

        $administrator = $this->userWithRole('administrator');
        $subject = $this->userWithRole('candidate');
        $assignee = $this->userWithRole('municipal_technician');

        $this->actingAs($administrator)
            ->post(route('backoffice.security.privacy.requests.store'), [
                'user_id' => $subject->id,
                'request_type' => 'access',
                'description' => 'Pedido RGPD criado pelo backoffice para validação controlada.',
            ])
            ->assertRedirect();

        $requestRecord = DataSubjectRequest::query()
            ->where('user_id', $subject->id)
            ->firstOrFail();

        $this->actingAs($administrator)
            ->post(route('backoffice.security.privacy.requests.assign', $requestRecord), [
                'assigned_to' => $assignee->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('data_subject_requests', [
            'id' => $requestRecord->id,
            'user_id' => $subject->id,
            'assigned_to' => $assignee->id,
        ]);

        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'data_subject_request.created',
            'subject_user_id' => $subject->id,
        ]);
    }

    public function test_retention_anonymization_permission_review_and_checklist_are_controlled(): void
    {
        $administrator = $this->userWithRole('administrator');

        $policy = RetentionPolicy::factory()->create([
            'entity_type' => User::class,
        ]);

        /** @var RetentionExecutionService $retention */
        $retention = app(RetentionExecutionService::class);

        $execution = $retention->simulate($policy, $administrator);

        $this->assertSame(
            RetentionExecutionStatus::Simulation,
            $execution->status,
        );

        $this->expectException(RuntimeException::class);

        $retention->run($execution, $administrator);
    }

    public function test_approved_retention_anonymization_and_failed_checklist_behaviour(): void
    {
        $administrator = $this->userWithRole('administrator');
        $target = $this->userWithRole('candidate');

        $originalEmail = $target->email;

        $policy = RetentionPolicy::factory()->create([
            'entity_type' => User::class,
        ]);

        /** @var RetentionExecutionService $retention */
        $retention = app(RetentionExecutionService::class);

        $execution = $retention->simulate($policy, $administrator);

        $retention->approve($execution, $administrator);
        $retention->run($execution->fresh(), $administrator);

        $this->assertSame(
            RetentionExecutionStatus::Completed,
            $execution->fresh()->status,
        );

        /** @var AnonymizationService $anonymizationService */
        $anonymizationService = app(AnonymizationService::class);

        $anonymization = $anonymizationService->create(
            [
                'user_id' => $target->id,
                'anonymization_type' => 'user_profile',
                'reason' => 'Pedido fictício de anonimização aprovado para teste.',
                'scope' => [
                    'user.profile',
                ],
            ],
            $administrator,
        );

        $anonymizationService->approve(
            $anonymization,
            $administrator,
        );

        $anonymizationService->run(
            $anonymization->fresh(),
            $administrator,
        );

        $updatedTarget = $target->fresh();

        $this->assertSame(
            "anon-{$target->id}@example.invalid",
            $updatedTarget->email,
        );

        $this->assertNotSame(
            $originalEmail,
            $updatedTarget->email,
        );

        /** @var PermissionReviewService $permissionReviewService */
        $permissionReviewService = app(PermissionReviewService::class);

        $review = $permissionReviewService->create($administrator);

        $this->assertGreaterThanOrEqual(
            1,
            $review->items()->count(),
        );

        /** @var PreProductionSecurityChecklistService $checklistService */
        $checklistService = app(
            PreProductionSecurityChecklistService::class,
        );

        $checklist = $checklistService->create($administrator);

        $item = $checklist->items()->firstOrFail();

        $checklistService->updateItem(
            $item,
            $administrator,
            SecurityChecklistStatus::Failed->value,
            'Falha demo.',
        );

        $this->expectException(RuntimeException::class);

        $checklistService->approve(
            $checklist->fresh(),
            $administrator,
        );
    }

    private function userWithRole(string $role): User
    {
        $this->assertDatabaseHas('roles', [
            'name' => $role,
        ]);

        $user = User::factory()->create();

        $user->assignRole($role);

        return $user;
    }
}
