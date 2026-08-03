<?php

namespace Tests\Feature;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseReviewResult;
use App\Enums\CorrectionRevalidationAggregateResult;
use App\Enums\FeatureKey;
use App\Models\ApplicationReviewBatch;
use App\Models\AuditLog;
use App\Models\CorrectionRequest;
use App\Models\CorrectionResponse;
use App\Models\User;
use App\Services\Administrative\CandidateCorrectionWorkspaceService;
use App\Services\Administrative\CorrectionDifferentialResolver;
use App\Services\Administrative\CorrectionResolutionService;
use App\Services\Administrative\CorrectionRevalidationService;
use App\Services\Administrative\CorrectionSubmissionService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesPublishedCorrectionRequests;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class Sprint53FCorrectionRevalidationTest extends TestCase
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

    public function test_submitted_request_is_visible_and_revalidation_start_is_idempotent(): void
    {
        [$request, , $operator] = $this->submittedRequest();

        $this->actingAs($operator)
            ->withSession(['mfa.verified_at' => now()])
            ->get(route('backoffice.correction-revalidations.index'))
            ->assertOk()
            ->assertSee($request->request_number)
            ->assertDontSee('documento-teste.pdf');

        $service = app(CorrectionRevalidationService::class);
        $first = $service->start($request, $operator);
        $second = $service->start($request->refresh(), $operator);

        $this->assertSame($first->revalidation_started_at?->toISOString(), $second->revalidation_started_at?->toISOString());
        $this->assertSame(
            AdministrativeProcessStatus::CorrectionUnderReview,
            $request->administrativeProcess()->firstOrFail()->status,
        );
        $this->assertSame(1, AuditLog::query()
            ->where('action', 'correction_revalidation_started')
            ->where('auditable_id', $request->id)
            ->count());
    }

    public function test_only_a_canonical_submitted_request_can_start_revalidation(): void
    {
        [$request, , $operator] = $this->submittedRequest();
        $request->forceFill(['status' => CorrectionRequestStatus::Open])->save();

        try {
            app(CorrectionRevalidationService::class)->start(
                $request->refresh(),
                $operator,
            );
            $this->fail('Era esperada uma falha fechada para um pedido não submetido.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('revalidation', $exception->errors());
        }

        $this->assertNull(CorrectionRequest::query()
            ->findOrFail($request->id)
            ->revalidation_started_at);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'correction_revalidation_started',
            'auditable_id' => $request->id,
        ]);
    }

    public function test_reviewable_item_decisions_are_idempotent_and_use_optimistic_tokens(): void
    {
        [$request, $response, $operator] = $this->submittedRequest();
        $service = app(CorrectionRevalidationService::class);
        $service->start($request, $operator);
        $item = app(CorrectionDifferentialResolver::class)
            ->resolve($request->refresh())
            ->reviewableItems()[0];

        $accepted = $service->decide(
            request: $request->refresh(),
            response: $response,
            result: CorrectionResponseReviewResult::Accepted,
            reviewNotes: 'Elemento conferido na segunda análise.',
            sourceFingerprint: $item->sourceFingerprint,
            expectedDecisionToken: null,
            actor: $operator,
        );
        $auditCount = AuditLog::query()
            ->where('action', 'correction_item_reviewed')
            ->count();

        $same = $service->decide(
            request: $request->refresh(),
            response: $accepted,
            result: CorrectionResponseReviewResult::Accepted,
            reviewNotes: 'Elemento conferido na segunda análise.',
            sourceFingerprint: $item->sourceFingerprint,
            expectedDecisionToken: null,
            actor: $operator,
        );

        $this->assertSame($accepted->id, $same->id);
        $this->assertSame($auditCount, AuditLog::query()
            ->where('action', 'correction_item_reviewed')
            ->count());

        try {
            $service->decide(
                request: $request->refresh(),
                response: $accepted->refresh(),
                result: CorrectionResponseReviewResult::Rejected,
                reviewNotes: 'Decisão concorrente fictícia.',
                sourceFingerprint: $item->sourceFingerprint,
                expectedDecisionToken: str_repeat('0', 64),
                actor: $operator,
            );
            $this->fail('Era esperado um conflito otimista controlado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('decision', $exception->errors());
        }

        $this->assertSame(
            CorrectionResponseReviewResult::Accepted,
            CorrectionResponse::query()
                ->findOrFail($accepted->id)
                ->review_result,
        );
    }

    public function test_complete_revalidation_can_be_previewed_and_sealed_once(): void
    {
        [$request, $response, $operator] = $this->submittedRequest();
        $revalidation = app(CorrectionRevalidationService::class);
        $resolution = app(CorrectionResolutionService::class);
        $revalidation->start($request, $operator);
        $item = app(CorrectionDifferentialResolver::class)
            ->resolve($request->refresh())
            ->reviewableItems()[0];
        $revalidation->decide(
            request: $request->refresh(),
            response: $response,
            result: CorrectionResponseReviewResult::Accepted,
            reviewNotes: 'Documento validado na segunda análise.',
            sourceFingerprint: $item->sourceFingerprint,
            expectedDecisionToken: null,
            actor: $operator,
        );

        $preview = $resolution->preview(
            $request->refresh(),
            $operator,
            'Fecho técnico após revalidação integral.',
        );
        $first = $resolution->seal(
            $request->refresh(),
            $operator,
            $preview['reason'],
            $preview['token'],
        );
        $second = $resolution->seal(
            $request->refresh(),
            $operator,
            $preview['reason'],
            $preview['token'],
        );

        $this->assertSame($first->id, $second->id);
        $this->assertSame(ApplicationReviewBatchCycle::Revalidation, $first->cycle);
        $this->assertSame(1, $first->items()->count());
        $this->assertSame(
            ApplicationReviewBatchOutcome::CompletePendingDecision,
            $first->items()->firstOrFail()->outcome,
        );
        $this->assertSame(1, ApplicationReviewBatch::query()
            ->where('correction_request_id', $request->id)
            ->count());
        $this->assertSame(
            CorrectionRequestStatus::Submitted,
            CorrectionRequest::query()->findOrFail($request->id)->status,
        );
        $this->assertSame(
            AdministrativeProcessStatus::CorrectionUnderReview,
            $request->administrativeProcess()->firstOrFail()->status,
        );
        $this->assertSame(1, AuditLog::query()
            ->where('action', 'correction_revalidation_sealed')
            ->count());
    }

    public function test_rejection_maps_to_non_automatic_final_outcome(): void
    {
        [$request, $response, $operator] = $this->submittedRequest();
        $revalidation = app(CorrectionRevalidationService::class);
        $resolution = app(CorrectionResolutionService::class);
        $revalidation->start($request, $operator);
        $item = app(CorrectionDifferentialResolver::class)
            ->resolve($request->refresh())
            ->reviewableItems()[0];
        $revalidation->decide(
            request: $request->refresh(),
            response: $response,
            result: CorrectionResponseReviewResult::Rejected,
            reviewNotes: 'O elemento mantém a desconformidade documental.',
            sourceFingerprint: $item->sourceFingerprint,
            expectedDecisionToken: null,
            actor: $operator,
        );
        $preview = $resolution->preview(
            $request->refresh(),
            $operator,
            'Fecho técnico com elemento não aceite.',
        );
        $batch = $resolution->seal(
            $request->refresh(),
            $operator,
            $preview['reason'],
            $preview['token'],
        );

        $this->assertSame(
            CorrectionRevalidationAggregateResult::Rejected,
            $preview['aggregate_result'],
        );
        $this->assertSame(
            ApplicationReviewBatchOutcome::CorrectionRejected,
            $batch->items()->firstOrFail()->outcome,
        );
        $this->assertSame(
            CorrectionRequestStatus::Submitted,
            CorrectionRequest::query()->findOrFail($request->id)->status,
        );
    }

    public function test_manual_decision_and_incomplete_review_block_sealing(): void
    {
        [$request, $response, $operator] = $this->submittedRequest();
        $revalidation = app(CorrectionRevalidationService::class);
        $revalidation->start($request, $operator);

        try {
            app(CorrectionResolutionService::class)->preview(
                $request->refresh(),
                $operator,
                'Tentativa prematura de fecho.',
            );
            $this->fail('Era esperado bloqueio de revisão incompleta.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('revalidation', $exception->errors());
        }

        $item = app(CorrectionDifferentialResolver::class)
            ->resolve($request->refresh())
            ->reviewableItems()[0];
        $revalidation->decide(
            request: $request->refresh(),
            response: $response,
            result: CorrectionResponseReviewResult::RequiresManualDecision,
            reviewNotes: 'Necessária decisão hierárquica municipal.',
            sourceFingerprint: $item->sourceFingerprint,
            expectedDecisionToken: null,
            actor: $operator,
        );

        try {
            app(CorrectionResolutionService::class)->preview(
                $request->refresh(),
                $operator,
                'Tentativa de fecho com decisão manual.',
            );
            $this->fail('Era esperado bloqueio por decisão manual pendente.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('revalidation', $exception->errors());
        }

        $this->assertDatabaseCount('application_review_batches', 1);
    }

    /** @return array{CorrectionRequest, CorrectionResponse, User} */
    private function submittedRequest(): array
    {
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
        $candidate = $request->candidate()->firstOrFail();
        $item = $request->items()->firstOrFail();
        $request->administrativeProcess()->update([
            'assigned_to' => $operator->id,
        ]);
        $response = app(CandidateCorrectionWorkspaceService::class)->save(
            request: $request->refresh(),
            item: $item,
            data: [],
            file: UploadedFile::fake()->create(
                'documento-teste.pdf',
                32,
                'application/pdf',
            ),
            candidate: $candidate,
        );
        app(CorrectionSubmissionService::class)->submit(
            $request->refresh(),
            $candidate,
        );

        return [
            $request->refresh()->load(['submissionReceipt', 'responses']),
            $response->refresh(),
            $operator->refresh(),
        ];
    }
}
