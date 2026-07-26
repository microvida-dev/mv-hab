<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Enums\NotificationPriority;
use App\Models\CommunicationLog;
use App\Models\InternalAlert;
use App\Models\Municipality;
use App\Models\NotificationEventRule;
use App\Models\NotificationTemplate;
use App\Models\PlatformOperatorAssignment;
use App\Models\ReportExport;
use App\Models\ReportRun;
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Notifications\CommunicationLogService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ReportingCommunicationsNotificationsConfigMunicipalScopeTest extends TestCase
{
    use RefreshDatabase;

    private Municipality $municipalityA;

    private Municipality $municipalityB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        $this->municipalityA = Municipality::factory()->create();
        $this->municipalityB = Municipality::factory()->create();
    }

    public function test_reporting_communications_and_alerts_are_scoped_before_querying(): void
    {
        $actorA = $this->municipalUser($this->municipalityA);
        $actorB = $this->municipalUser($this->municipalityB);
        $runA = ReportRun::factory()->create(['user_id' => $actorA->id]);
        $runB = ReportRun::factory()->create(['user_id' => $actorB->id]);
        $exportA = ReportExport::factory()->create([
            'report_run_id' => $runA->id,
            'user_id' => $actorA->id,
        ]);
        $exportB = ReportExport::factory()->create([
            'report_run_id' => $runB->id,
            'user_id' => $actorB->id,
        ]);
        $communicationA = CommunicationLog::factory()->create([
            'municipality_id' => $this->municipalityA->id,
            'recipient_user_id' => $actorA->id,
            'created_by' => $actorA->id,
        ]);
        $communicationB = CommunicationLog::factory()->create([
            'municipality_id' => $this->municipalityB->id,
            'recipient_user_id' => $actorB->id,
            'created_by' => $actorB->id,
        ]);
        $alertA = InternalAlert::factory()->create([
            'municipality_id' => $this->municipalityA->id,
            'assigned_to' => $actorA->id,
        ]);
        $alertB = InternalAlert::factory()->create([
            'municipality_id' => $this->municipalityB->id,
            'assigned_to' => $actorB->id,
        ]);
        $scope = app(MunicipalRecordScopeService::class);

        $this->assertSame(
            [$runA->id],
            $scope->reportRuns(ReportRun::query(), $actorA)
                ->pluck('id')
                ->all(),
        );
        $this->assertSame(
            [$exportA->id],
            $scope->reportExports(ReportExport::query(), $actorA)
                ->pluck('id')
                ->all(),
        );
        $this->assertSame(
            [$communicationA->id],
            $scope->communicationLogs(CommunicationLog::query(), $actorA)
                ->pluck('id')
                ->all(),
        );
        $this->assertSame(
            [$alertA->id],
            $scope->internalAlerts(InternalAlert::query(), $actorA)
                ->pluck('id')
                ->all(),
        );
        $this->assertFalse(
            $scope->ownsReportExport($actorA, $exportB),
        );
        $this->assertFalse(
            $scope->ownsCommunicationLog($actorA, $communicationB),
        );
        $this->assertFalse(
            $scope->ownsInternalAlert($actorA, $alertB),
        );
    }

    public function test_system_catalog_is_readable_but_only_local_catalog_is_mutable(): void
    {
        $actorA = $this->municipalUser($this->municipalityA);
        $systemTemplate = NotificationTemplate::factory()->create([
            'municipality_id' => null,
            'program_id' => null,
            'contest_id' => null,
        ]);
        $localTemplate = NotificationTemplate::factory()->create([
            'municipality_id' => $this->municipalityA->id,
        ]);
        $foreignTemplate = NotificationTemplate::factory()->create([
            'municipality_id' => $this->municipalityB->id,
        ]);
        $systemRule = NotificationEventRule::factory()->create([
            'municipality_id' => null,
            'program_id' => null,
            'contest_id' => null,
            'notification_template_id' => $systemTemplate->id,
        ]);
        $localRule = NotificationEventRule::factory()->create([
            'municipality_id' => $this->municipalityA->id,
            'notification_template_id' => $localTemplate->id,
        ]);
        $foreignRule = NotificationEventRule::factory()->create([
            'municipality_id' => $this->municipalityB->id,
            'notification_template_id' => $foreignTemplate->id,
        ]);
        $scope = app(MunicipalRecordScopeService::class);

        $this->assertEqualsCanonicalizing(
            [$systemTemplate->id, $localTemplate->id],
            $scope->notificationTemplates(
                NotificationTemplate::query(),
                $actorA,
            )->pluck('id')->all(),
        );
        $this->assertTrue(
            $scope->ownsNotificationTemplate(
                $actorA,
                $systemTemplate,
            ),
        );
        $this->assertFalse(
            $scope->canMutateNotificationTemplate(
                $actorA,
                $systemTemplate,
            ),
        );
        $this->assertTrue(
            $scope->canMutateNotificationTemplate(
                $actorA,
                $localTemplate,
            ),
        );
        $this->assertFalse(
            $scope->ownsNotificationTemplate(
                $actorA,
                $foreignTemplate,
            ),
        );

        $this->assertEqualsCanonicalizing(
            [$systemRule->id, $localRule->id],
            $scope->notificationEventRules(
                NotificationEventRule::query(),
                $actorA,
            )->pluck('id')->all(),
        );
        $this->assertFalse(
            $scope->canMutateNotificationEventRule(
                $actorA,
                $systemRule,
            ),
        );
        $this->assertTrue(
            $scope->canMutateNotificationEventRule(
                $actorA,
                $localRule,
            ),
        );
        $this->assertFalse(
            $scope->ownsNotificationEventRule(
                $actorA,
                $foreignRule,
            ),
        );
    }

    public function test_global_scope_requires_active_assignment_and_account(): void
    {
        $ownerA = $this->municipalUser($this->municipalityA);
        $ownerB = $this->municipalUser($this->municipalityB);
        ReportRun::factory()->create(['user_id' => $ownerA->id]);
        ReportRun::factory()->create(['user_id' => $ownerB->id]);

        $activeOperator = User::factory()->withoutMunicipality()->create([
            'status' => 'active',
        ]);
        PlatformOperatorAssignment::factory()->create([
            'user_id' => $activeOperator->id,
        ]);
        $revokedOperator = User::factory()->withoutMunicipality()->create([
            'status' => 'active',
        ]);
        PlatformOperatorAssignment::factory()
            ->revoked()
            ->create(['user_id' => $revokedOperator->id]);
        $inactiveOperator = User::factory()->withoutMunicipality()->create([
            'status' => 'active',
        ]);
        PlatformOperatorAssignment::factory()->create([
            'user_id' => $inactiveOperator->id,
        ]);
        $inactiveOperator->forceFill(['status' => 'inactive'])->save();
        $withoutMunicipality = User::factory()
            ->withoutMunicipality()
            ->create(['status' => 'active']);
        $scope = app(MunicipalRecordScopeService::class);

        $this->assertSame(
            2,
            $scope->reportRuns(
                ReportRun::query(),
                $activeOperator,
            )->count(),
        );
        $this->assertSame(
            0,
            $scope->reportRuns(
                ReportRun::query(),
                $revokedOperator,
            )->count(),
        );
        $this->assertSame(
            0,
            $scope->reportRuns(
                ReportRun::query(),
                $inactiveOperator,
            )->count(),
        );
        $this->assertSame(
            0,
            $scope->reportRuns(
                ReportRun::query(),
                $withoutMunicipality,
            )->count(),
        );
    }

    public function test_communication_creation_requires_canonical_non_recipient_origin(): void
    {
        $actorA = $this->municipalUser($this->municipalityA);
        $recipientA = $this->municipalUser($this->municipalityA);
        $recipientB = $this->municipalUser($this->municipalityB);
        $service = app(CommunicationLogService::class);

        $communication = $service->create(
            eventCode: 'test.local',
            recipient: $recipientA,
            content: [
                'subject' => 'Assunto local',
                'body' => 'Conteúdo fictício.',
            ],
            actor: $actorA,
            priority: NotificationPriority::Normal,
        );

        $this->assertSame(
            $this->municipalityA->id,
            $communication->municipality_id,
        );

        try {
            $service->create(
                eventCode: 'test.foreign',
                recipient: $recipientB,
                content: ['body' => 'Conteúdo fictício.'],
                actor: $actorA,
            );
            self::fail('A comunicação intermunicipal deveria falhar.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('communication_logs', [
                'event_code' => 'test.foreign',
            ]);
        }

        $globalActor = User::factory()->withoutMunicipality()->create([
            'status' => 'active',
        ]);
        PlatformOperatorAssignment::factory()->create([
            'user_id' => $globalActor->id,
        ]);

        $this->expectException(ValidationException::class);

        $service->create(
            eventCode: 'test.recipient_only',
            recipient: $recipientA,
            content: ['body' => 'Conteúdo fictício.'],
            actor: $globalActor,
        );
    }

    private function municipalUser(Municipality $municipality): User
    {
        return User::factory()->create([
            'municipality_id' => $municipality->id,
            'status' => 'active',
        ]);
    }
}
