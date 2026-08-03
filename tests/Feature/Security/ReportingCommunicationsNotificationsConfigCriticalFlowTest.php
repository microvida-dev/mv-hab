<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDeliveryStatus;
use App\Enums\CommunicationReceiptType;
use App\Jobs\GenerateCommunicationReceiptJob;
use App\Jobs\ProcessPendingCommunicationsJob;
use App\Jobs\SendCommunicationDeliveryJob;
use App\Models\CommunicationAttempt;
use App\Models\CommunicationLog;
use App\Models\CommunicationReceipt;
use App\Models\Municipality;
use App\Models\ReportDefinition;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use App\Services\Municipalities\CommunicationMunicipalContextService;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Notifications\CommunicationDeliveryService;
use App\Services\Notifications\CommunicationLogService;
use App\Services\Notifications\CommunicationReceiptService;
use App\Services\Platform\PlatformOperatorScopeService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class ReportingCommunicationsNotificationsConfigCriticalFlowTest extends TestCase
{
    use RefreshDatabase;

    private Municipality $municipalityA;

    private Municipality $municipalityB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        Storage::fake('local');
        $this->municipalityA = Municipality::factory()->create();
        $this->municipalityB = Municipality::factory()->create();
    }

    public function test_private_report_download_is_scoped_audited_and_fails_closed(): void
    {
        $administratorA = $this->userWithRole(
            'administrator',
            $this->municipalityA,
        );
        $administratorB = $this->userWithRole(
            'administrator',
            $this->municipalityB,
        );
        $local = $this->reportExportFor(
            $administratorA,
            'reports/tests/local/report.csv',
        );
        $foreign = $this->reportExportFor(
            $administratorB,
            'reports/tests/foreign/report.csv',
        );

        $this->getAs(
            $administratorA,
            route('backoffice.reports.exports.download', $local),
        )->assertOk();

        $this->assertDatabaseHas('report_download_logs', [
            'report_export_id' => $local->id,
            'user_id' => $administratorA->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'report.export.downloaded',
            'auditable_id' => $local->id,
            'module' => 'reports',
        ]);

        $this->getAs(
            $administratorA,
            route('backoffice.reports.exports.download', $foreign),
        )->assertForbidden();

        $local->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->getAs(
            $administratorA,
            route('backoffice.reports.exports.download', $local),
        )->assertNotFound();

        $missing = $this->reportExportFor(
            $administratorA,
            'reports/tests/missing/report.csv',
            writeFile: false,
        );

        $this->getAs(
            $administratorA,
            route('backoffice.reports.exports.download', $missing),
        )->assertNotFound();
    }

    public function test_auditor_cannot_download_exports_or_mutate_alerts(): void
    {
        $auditor = $this->userWithRole(
            'auditor',
            $this->municipalityA,
        );
        $export = $this->reportExportFor(
            $auditor,
            'reports/tests/auditor/report.csv',
        );

        $this->getAs(
            $auditor,
            route('backoffice.reports.exports.download', $export),
        )->assertForbidden();

        $this->actingAs($auditor)
            ->withSession(['mfa.verified_at' => now()])
            ->post(route('backoffice.internal-alerts.detect'))
            ->assertForbidden();

        $this->assertDatabaseCount('internal_alerts', 0);
    }

    public function test_candidate_is_blocked_from_backoffice_direct_urls(): void
    {
        $candidate = $this->userWithRole(
            'candidate',
            $this->municipalityA,
        );

        foreach ([
            route('backoffice.communications.index'),
            route('backoffice.communications.templates.index'),
            route('backoffice.reports.dashboard'),
        ] as $url) {
            $this->actingAs($candidate)
                ->withSession(['mfa.verified_at' => now()])
                ->get($url)
                ->assertForbidden();
        }
    }

    public function test_mfa_remains_an_independent_backoffice_guard(): void
    {
        $supportAgent = $this->userWithRole(
            'support_agent',
            $this->municipalityA,
            mfaRequired: true,
        );

        $this->actingAs($supportAgent)
            ->get(route('backoffice.communications.index'))
            ->assertRedirect(route('backoffice.security.mfa.index'));
    }

    public function test_pending_job_transports_canonical_context_and_omits_invalid_records(): void
    {
        Queue::fake();
        $actor = $this->userWithRole(
            'administrator',
            $this->municipalityA,
        );
        $recipient = User::factory()->create([
            'municipality_id' => $this->municipalityA->id,
        ]);
        $communication = $this->communicationFor($actor, $recipient);
        $delivery = app(CommunicationDeliveryService::class)->create(
            $communication,
            CommunicationChannel::InApp,
        );
        $invalidCommunication = CommunicationLog::factory()->create([
            'municipality_id' => null,
            'recipient_user_id' => $recipient->id,
            'created_by' => $actor->id,
        ]);
        app(CommunicationDeliveryService::class)->create(
            $invalidCommunication,
            CommunicationChannel::InApp,
        );

        app(ProcessPendingCommunicationsJob::class)->handle(
            app(CommunicationMunicipalContextService::class),
        );

        Queue::assertPushed(
            SendCommunicationDeliveryJob::class,
            fn (SendCommunicationDeliveryJob $job): bool => (
                $job->deliveryId === $delivery->id
                && $job->actorId === $actor->id
                && $job->municipalityId === $this->municipalityA->id
                && $job->permissionContext === 'system.scheduler'
                && $job->systemInitiated
                && ($job->auditMetadata['source'] ?? null)
                    === 'pending_communications'
            ),
        );
        Queue::assertPushed(
            SendCommunicationDeliveryJob::class,
            1,
        );
    }

    public function test_delivery_job_revalidates_context_and_is_idempotent(): void
    {
        $actor = $this->userWithRole(
            'administrator',
            $this->municipalityA,
        );
        $recipient = User::factory()->create([
            'municipality_id' => $this->municipalityA->id,
        ]);
        $communication = $this->communicationFor($actor, $recipient);
        $delivery = app(CommunicationDeliveryService::class)->create(
            $communication,
            CommunicationChannel::InApp,
        );
        $job = new SendCommunicationDeliveryJob(
            deliveryId: $delivery->id,
            actorId: $actor->id,
            municipalityId: $this->municipalityA->id,
            permissionContext: 'communications.create',
            systemInitiated: false,
            auditMetadata: ['source' => 'test'],
        );

        $job->handle(
            app(CommunicationDeliveryService::class),
            app(CommunicationMunicipalContextService::class),
        );

        $this->assertSame(
            CommunicationDeliveryStatus::Delivered,
            $delivery->refresh()->status,
        );
        $this->assertSame(
            1,
            CommunicationAttempt::query()
                ->where('communication_delivery_id', $delivery->id)
                ->count(),
        );
        $this->assertSame(
            1,
            CommunicationReceipt::query()
                ->where('communication_delivery_id', $delivery->id)
                ->count(),
        );
        $this->assertDatabaseHas('audit_events', [
            'event_code' => 'communication_delivery_processed',
            'auditable_id' => $delivery->id,
            'municipality_id' => $this->municipalityA->id,
        ]);

        $job->handle(
            app(CommunicationDeliveryService::class),
            app(CommunicationMunicipalContextService::class),
        );

        $this->assertSame(
            1,
            CommunicationAttempt::query()
                ->where('communication_delivery_id', $delivery->id)
                ->count(),
        );
        $this->assertSame(
            1,
            CommunicationReceipt::query()
                ->where('communication_delivery_id', $delivery->id)
                ->count(),
        );
    }

    public function test_delivery_job_rejects_stale_municipality_context(): void
    {
        $actor = $this->userWithRole(
            'administrator',
            $this->municipalityA,
        );
        $recipient = User::factory()->create([
            'municipality_id' => $this->municipalityA->id,
        ]);
        $communication = $this->communicationFor($actor, $recipient);
        $delivery = app(CommunicationDeliveryService::class)->create(
            $communication,
            CommunicationChannel::InApp,
        );

        $this->expectException(HttpException::class);

        (new SendCommunicationDeliveryJob(
            deliveryId: $delivery->id,
            actorId: $actor->id,
            municipalityId: $this->municipalityB->id,
            permissionContext: 'communications.create',
        ))->handle(
            app(CommunicationDeliveryService::class),
            app(CommunicationMunicipalContextService::class),
        );
    }

    public function test_receipt_job_rejects_delivery_from_another_communication(): void
    {
        $actorA = $this->userWithRole(
            'administrator',
            $this->municipalityA,
        );
        $recipientA = User::factory()->create([
            'municipality_id' => $this->municipalityA->id,
        ]);
        $communicationA = $this->communicationFor($actorA, $recipientA);
        $actorB = $this->userWithRole(
            'administrator',
            $this->municipalityB,
        );
        $recipientB = User::factory()->create([
            'municipality_id' => $this->municipalityB->id,
        ]);
        $communicationB = $this->communicationFor($actorB, $recipientB);
        $deliveryB = app(CommunicationDeliveryService::class)->create(
            $communicationB,
            CommunicationChannel::InApp,
        );
        $job = new GenerateCommunicationReceiptJob(
            communicationId: $communicationA->id,
            type: CommunicationReceiptType::SendProof->value,
            deliveryId: $deliveryB->id,
            actorId: $actorA->id,
            municipalityId: $this->municipalityA->id,
            permissionContext: 'communications.create',
        );

        try {
            $job->handle(
                app(CommunicationReceiptService::class),
                app(CommunicationMunicipalContextService::class),
                app(MunicipalRecordScopeService::class),
                app(PlatformOperatorScopeService::class),
                app(AuditTrailService::class),
            );
            self::fail('A entrega de outra comunicação deveria ser rejeitada.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertDatabaseMissing('communication_receipts', [
            'communication_log_id' => $communicationA->id,
            'communication_delivery_id' => $deliveryB->id,
        ]);
    }

    private function communicationFor(
        User $actor,
        User $recipient,
    ): CommunicationLog {
        return app(CommunicationLogService::class)->create(
            eventCode: 'test.critical',
            recipient: $recipient,
            content: [
                'subject' => 'Comunicação de teste',
                'body' => 'Conteúdo exclusivamente fictício.',
            ],
            actor: $actor,
        );
    }

    private function reportExportFor(
        User $owner,
        string $path,
        bool $writeFile = true,
    ): ReportExport {
        $definition = ReportDefinition::factory()->create();
        $run = ReportRun::factory()->create([
            'report_definition_id' => $definition->id,
            'user_id' => $owner->id,
        ]);
        $export = ReportExport::factory()->create([
            'report_run_id' => $run->id,
            'user_id' => $owner->id,
            'disk' => 'local',
            'file_path' => $path,
            'file_name' => basename($path),
            'expires_at' => now()->addHour(),
        ]);

        if ($writeFile) {
            Storage::disk('local')->put($path, "coluna\nvalor\n");
        }

        return $export;
    }

    private function userWithRole(
        string $role,
        Municipality $municipality,
        bool $mfaRequired = false,
    ): User {
        $user = User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
            'mfa_required' => $mfaRequired,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function getAs(User $user, string $url): TestResponse
    {
        return $this->actingAs($user)
            ->withSession(['mfa.verified_at' => now()])
            ->get($url);
    }
}
