<?php

namespace Tests\Feature;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseReviewResult;
use App\Enums\CorrectionRevalidationAggregateResult;
use App\Enums\FeatureKey;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewPublication;
use App\Models\AuditLog;
use App\Models\CorrectionRequest;
use App\Models\OfficialNotification;
use App\Models\User;
use App\Services\Administrative\ApplicationReviewPublicationService;
use App\Services\Administrative\CandidateCorrectionWorkspaceService;
use App\Services\Administrative\CorrectionDifferentialResolver;
use App\Services\Administrative\CorrectionProgressMetricsService;
use App\Services\Administrative\CorrectionResolutionService;
use App\Services\Administrative\CorrectionRevalidationService;
use App\Services\Administrative\CorrectionSubmissionService;
use App\Services\Administrative\PublishedCorrectionRevalidationProjector;
use App\Services\Agenda\AgendaService;
use App\Services\Dashboard\Timeline\Providers\CorrectionRequestTimelineProvider;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\CreatesPublishedCorrectionRequests;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class Sprint53FCorrectionPublicationTest extends TestCase
{
    use CreatesPublishedCorrectionRequests;
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemAccessSeeder::class);
        Storage::fake('local');
        Queue::fake();
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_accepted_revalidation_is_published_projected_and_notified_once(): void
    {
        $this->travelTo(Carbon::parse(
            '2026-08-03 00:15:00',
            (string) config('app.timezone', 'Europe/Lisbon'),
        ));

        [$request, $operator, $batch] = $this->sealedRequest(
            CorrectionResponseReviewResult::Accepted,
        );
        $originalApplicationStatus = $request->application()->firstOrFail()->status;

        [$publication, $payload] = $this->publish($batch, $operator);
        $result = $publication->results()->sole();
        $projected = $request->refresh();

        $this->assertSame(CorrectionRequestStatus::Resolved, $projected->status);
        $this->assertSame(
            CorrectionRevalidationAggregateResult::Accepted,
            $projected->revalidation_result,
        );
        $this->assertSame($result->id, $projected->revalidation_publication_result_id);
        $this->assertNotNull($projected->revalidation_projected_at);
        $this->assertNotNull($projected->resolved_at);
        $this->assertNotNull($projected->closed_at);
        $this->assertSame(
            AdministrativeProcessStatus::EligibilityReview,
            $projected->administrativeProcess()->firstOrFail()->status,
        );
        $this->assertSame(
            $originalApplicationStatus,
            $projected->application()->firstOrFail()->status,
        );
        $this->assertSame(1, CorrectionRequest::query()
            ->where('application_id', $projected->application_id)
            ->count());
        $this->assertSame(1, OfficialNotification::query()
            ->where('notifiable_type', $publication->getMorphClass())
            ->where('notifiable_id', $publication->id)
            ->count());

        $notificationCount = OfficialNotification::query()->count();
        $auditCount = AuditLog::query()
            ->whereIn('action', [
                'correction_revalidation_published',
                'correction_request_resolved',
                'correction_revalidation_projected',
            ])
            ->count();
        $repeated = app(ApplicationReviewPublicationService::class)->publish(
            $batch->refresh(),
            $operator,
            $payload,
        );
        $directRetry = app(PublishedCorrectionRevalidationProjector::class)
            ->project($result->refresh(), $operator);

        $this->assertSame($publication->id, $repeated->id);
        $this->assertSame($projected->id, $directRetry->id);
        $this->assertSame($notificationCount, OfficialNotification::query()->count());
        $this->assertSame($auditCount, AuditLog::query()
            ->whereIn('action', [
                'correction_revalidation_published',
                'correction_request_resolved',
                'correction_revalidation_projected',
            ])
            ->count());

        $candidate = $projected->candidate()->firstOrFail();
        $this->actingAs($candidate)
            ->get(route('candidate.application-review-results.show', $result))
            ->assertOk()
            ->assertSee('Completa, a aguardar decisão')
            ->assertDontSee('documento-revalidacao.pdf');

        $metrics = app(CorrectionProgressMetricsService::class)
            ->municipalDashboard($operator);
        $this->assertSame(1, $metrics['summary']['sealed_revalidations']);
        $this->assertSame(1, $metrics['summary']['published_revalidations']);
        $this->assertSame(1, $metrics['summary']['resolved_revalidations']);
        $this->assertSame(0, $metrics['summary']['rejected_revalidations']);
        $this->assertFalse($metrics['summary']['revalidation_sla_configured']);
        $this->assertNull($metrics['summary']['revalidation_sla_overdue_requests']);
        $serializedMetrics = json_encode($metrics, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('documento-revalidacao.pdf', $serializedMetrics);
        $this->assertStringNotContainsString(
            'Decisão municipal de teste da segunda análise.',
            $serializedMetrics,
        );

        $event = collect(app(CorrectionRequestTimelineProvider::class)
            ->forUser($operator))
            ->firstWhere('id', 'correction-request-'.$projected->id);
        $this->assertNotNull($event);
        $this->assertSame('Segunda análise concluída', $event->title);
        $this->assertSame('resolved', $event->metadata['revalidation_stage']);
        $this->assertArrayNotHasKey('document_submission_id', $event->metadata);
        $agendaIds = array_column(
            app(AgendaService::class)->today($operator)['events'] ?? [],
            'id',
        );
        $this->assertContains('correction-request-'.$projected->id, $agendaIds);
    }

    public function test_rejected_revalidation_returns_to_manual_eligibility_without_exclusion_or_third_request(): void
    {
        [$request, $operator, $batch] = $this->sealedRequest(
            CorrectionResponseReviewResult::Rejected,
        );

        [$publication] = $this->publish($batch, $operator);
        $projected = $request->refresh();

        $this->assertSame(CorrectionRequestStatus::Resolved, $projected->status);
        $this->assertSame(
            CorrectionRevalidationAggregateResult::Rejected,
            $projected->revalidation_result,
        );
        $this->assertSame(
            AdministrativeProcessStatus::EligibilityReview,
            $projected->administrativeProcess()->firstOrFail()->status,
        );
        $this->assertSame(1, CorrectionRequest::query()
            ->where('application_id', $projected->application_id)
            ->count());
        $this->assertSame(1, $publication->results()->count());
        $this->assertSame(1, AuditLog::query()
            ->where('action', 'correction_revalidation_rejected')
            ->where('auditable_id', $projected->id)
            ->count());

        $metrics = app(CorrectionProgressMetricsService::class)
            ->municipalDashboard($operator);
        $this->assertSame(1, $metrics['summary']['rejected_revalidations']);
        $event = collect(app(CorrectionRequestTimelineProvider::class)
            ->forUser($operator))
            ->firstWhere('id', 'correction-request-'.$projected->id);
        $this->assertNotNull($event);
        $this->assertSame('Aperfeiçoamento não aceite', $event->title);
        $this->assertSame('rejected', $event->metadata['revalidation_stage']);
    }

    public function test_foreign_municipal_actor_cannot_reproject_a_published_result(): void
    {
        [$request, $operator, $batch] = $this->sealedRequest(
            CorrectionResponseReviewResult::Accepted,
        );
        [$publication] = $this->publish($batch, $operator);
        $result = $publication->results()->sole();
        $foreignMunicipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationReview,
        );
        $foreignOperator = User::factory()->create(['status' => 'active']);
        $foreignOperator->assignRole('administrator');
        $this->assignMunicipality($foreignOperator, $foreignMunicipality);
        $auditCount = AuditLog::query()
            ->where('action', 'correction_revalidation_projected')
            ->count();

        try {
            app(PublishedCorrectionRevalidationProjector::class)->project(
                $result,
                $foreignOperator,
            );
            $this->fail('Era esperada recusa por isolamento municipal.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame($auditCount, AuditLog::query()
            ->where('action', 'correction_revalidation_projected')
            ->count());
        $this->assertSame(
            $result->id,
            $request->refresh()->revalidation_publication_result_id,
        );
    }

    public function test_projection_failure_rolls_back_publication_communication_and_notification(): void
    {
        [$request, $operator, $batch] = $this->sealedRequest(
            CorrectionResponseReviewResult::Accepted,
        );
        $service = app(ApplicationReviewPublicationService::class);
        $reason = 'Publicação final cuja projeção deve falhar fechada.';
        $preview = $service->preview(
            $batch->refresh()->load(['contest.program', 'items']),
            $operator,
            $reason,
        );
        $notificationCount = OfficialNotification::query()->count();
        $publicationCount = ApplicationReviewPublication::query()->count();

        $request->administrativeProcess()->firstOrFail()->forceFill([
            'status' => AdministrativeProcessStatus::EligibilityReview,
        ])->save();

        try {
            $service->publish($batch->refresh(), $operator, [
                'reason' => $reason,
                'preview_token' => $preview['token'],
            ]);
            $this->fail('Era esperada uma falha fechada da projeção processual.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('revalidation', $exception->errors());
        }

        $this->assertSame($publicationCount, ApplicationReviewPublication::query()->count());
        $this->assertSame($notificationCount, OfficialNotification::query()->count());
        $this->assertNull($request->refresh()->revalidation_publication_result_id);
        $this->assertSame(CorrectionRequestStatus::Submitted, $request->status);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'correction_revalidation_projected',
            'auditable_id' => $request->id,
        ]);
    }

    /**
     * @return array{CorrectionRequest, User, ApplicationReviewBatch}
     */
    private function sealedRequest(
        CorrectionResponseReviewResult $decision,
    ): array {
        $municipality = $this->municipalityWithFeatures(
            FeatureKey::ApplicationReview,
        );
        $operator = User::factory()->create(['status' => 'active']);
        $operator->assignRole('administrator');
        $request = $this->createPublishedCorrectionRequest(
            municipality: $municipality,
            operator: $operator,
            status: CorrectionRequestStatus::Open,
            completedItems: 0,
            totalItems: 1,
            deadline: now()->addWeek(),
        );
        $request->administrativeProcess()->update([
            'assigned_to' => $operator->id,
        ]);
        $candidate = $request->candidate()->firstOrFail();
        $response = app(CandidateCorrectionWorkspaceService::class)->save(
            request: $request->refresh(),
            item: $request->items()->firstOrFail(),
            data: [],
            file: UploadedFile::fake()->create(
                'documento-revalidacao.pdf',
                32,
                'application/pdf',
            ),
            candidate: $candidate,
        );
        app(CorrectionSubmissionService::class)->submit(
            $request->refresh(),
            $candidate,
        );
        $revalidation = app(CorrectionRevalidationService::class);
        $revalidation->start($request->refresh(), $operator);
        $item = app(CorrectionDifferentialResolver::class)
            ->resolve($request->refresh())
            ->reviewableItems()[0];
        $revalidation->decide(
            request: $request->refresh(),
            response: $response->refresh(),
            result: $decision,
            reviewNotes: 'Decisão municipal de teste da segunda análise.',
            sourceFingerprint: $item->sourceFingerprint,
            expectedDecisionToken: null,
            actor: $operator,
        );
        $resolution = app(CorrectionResolutionService::class);
        $preview = $resolution->preview(
            $request->refresh(),
            $operator,
            'Fecho final da segunda análise para publicação.',
        );
        $batch = $resolution->seal(
            $request->refresh(),
            $operator,
            $preview['reason'],
            $preview['token'],
        );

        return [$request->refresh(), $operator->refresh(), $batch->refresh()];
    }

    /**
     * @return array{
     *     ApplicationReviewPublication,
     *     array{reason:string, preview_token:string}
     * }
     */
    private function publish(
        ApplicationReviewBatch $batch,
        User $operator,
    ): array {
        $service = app(ApplicationReviewPublicationService::class);
        $reason = 'Publicação oficial do resultado da segunda análise.';
        $preview = $service->preview(
            $batch->refresh()->load(['contest.program', 'items']),
            $operator,
            $reason,
        );
        $payload = [
            'reason' => $reason,
            'preview_token' => $preview['token'],
        ];

        return [
            $service->publish($batch->refresh(), $operator, $payload),
            $payload,
        ];
    }
}
