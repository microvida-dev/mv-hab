<?php

namespace Tests\Unit\Administrative;

use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionRequiredAction;
use App\Enums\CorrectionResponseReviewResult;
use App\Enums\CorrectionResponseStatus;
use App\Enums\CorrectionRevalidationAggregateResult;
use App\Enums\CorrectionRevalidationItemType;
use App\Enums\DocumentStatus;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRequestItem;
use App\Models\CorrectionResponse;
use App\Models\DocumentVersion;
use App\Models\Municipality;
use App\Models\User;
use App\Services\Administrative\CandidateCorrectionWorkspaceService;
use App\Services\Administrative\CorrectionDifferentialResolver;
use App\Services\Administrative\CorrectionRevalidationSnapshotBuilder;
use App\Services\Administrative\CorrectionSubmissionService;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesPublishedCorrectionRequests;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class CorrectionDifferentialResolverTest extends TestCase
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

    public function test_receipt_is_boundary_and_valid_documents_are_carried_forward(): void
    {
        [$request] = $this->submittedRequest([
            $this->validatedDocumentSnapshot(900001),
        ]);

        $differential = app(CorrectionDifferentialResolver::class)
            ->resolve($request);

        $this->assertFalse($differential->isStale());
        $this->assertCount(1, $differential->reviewableItems());
        $this->assertCount(1, $differential->carriedForwardItems());
        $this->assertSame(
            CorrectionRevalidationItemType::NewDocument,
            $differential->reviewableItems()[0]->classification,
        );
        $this->assertSame(
            CorrectionRevalidationItemType::UnchangedValid,
            $differential->carriedForwardItems()[0]->classification,
        );
        $this->assertSame(
            $request->submissionReceipt?->snapshot_hash,
            $differential->submissionReceipt->snapshot_hash,
        );
    }

    public function test_version_created_after_receipt_is_detected_as_stale(): void
    {
        [$request, $response] = $this->submittedRequest();
        $submission = $response->documentSubmission()->firstOrFail();
        $receiptVersion = $response->documentVersion()->firstOrFail();
        $laterVersion = DocumentVersion::factory()->create([
            'document_submission_id' => $submission->id,
            'replaces_document_version_id' => $receiptVersion->id,
            'version_number' => $receiptVersion->version_number + 1,
        ]);
        $submission->forceFill([
            'current_version_id' => $laterVersion->id,
        ])->save();

        $differential = app(CorrectionDifferentialResolver::class)
            ->resolve($request);

        $this->assertTrue($differential->isStale());
        $this->assertContains(
            'Existe uma versão documental posterior ao recibo formal.',
            $differential->blockers,
        );
        $this->assertSame(
            $receiptVersion->id,
            $differential->reviewableItems()[0]
                ->submittedDocumentVersionId,
        );
    }

    public function test_snapshot_is_deterministic_and_contains_decisions_and_carry_forward(): void
    {
        [$request] = $this->submittedRequest([
            $this->validatedDocumentSnapshot(900002),
        ]);
        $resolver = app(CorrectionDifferentialResolver::class);
        $differential = $resolver->resolve($request);
        $item = $differential->reviewableItems()[0];
        $response = CorrectionResponse::query()
            ->whereKey($item->correctionResponseId)
            ->firstOrFail();
        $reviewedAt = now('UTC')->startOfSecond();
        $response->forceFill([
            'status' => CorrectionResponseStatus::Accepted,
            'review_result' => CorrectionResponseReviewResult::Accepted,
            'reviewed_by' => $request->revalidation_started_by
                ?? $request->issued_by,
            'reviewed_at' => $reviewedAt,
            'review_notes' => 'Decisão técnica fictícia.',
            'differential_classification' => $item->classification,
            'decision_source_fingerprint' => $item->sourceFingerprint,
        ])->save();

        $differential = $resolver->resolve($request);
        $builder = app(CorrectionRevalidationSnapshotBuilder::class);
        $first = $builder->build(
            $differential,
            CorrectionRevalidationAggregateResult::Accepted,
        );
        $second = $builder->build(
            $resolver->resolve($request),
            CorrectionRevalidationAggregateResult::Accepted,
        );

        $this->assertSame($first['snapshot_hash'], $second['snapshot_hash']);
        $this->assertSame($first['payload'], $second['payload']);
        $this->assertSame(1, $first['payload']['schema_version']);
        $this->assertCount(1, $first['payload']['carried_forward_items']);
        $this->assertCount(1, $first['payload']['changed_items']);
        $this->assertCount(1, $first['payload']['decisions']);
        $this->assertSame(
            CorrectionRevalidationAggregateResult::Accepted->value,
            $first['payload']['aggregate_result']['value'],
        );
    }

    public function test_justification_and_dependency_are_reviewable_differential_items(): void
    {
        [$justificationRequest] = $this->submittedRequest(
            answerData: [
                'justification' => 'Fundamentação fictícia do candidato.',
            ],
            withDocument: false,
        );
        [$dependencyRequest] = $this->submittedRequest(
            answerData: [
                'response_text' => 'Esclarecimento fictício do candidato.',
            ],
            withDocument: false,
            configureItem: static function (
                CorrectionRequestItem $item,
            ): void {
                $item->forceFill([
                    'required_action' => CorrectionRequiredAction::ProvideExplanation,
                ])->save();
            },
        );

        $resolver = app(CorrectionDifferentialResolver::class);
        $justification = $resolver->resolve($justificationRequest);
        $dependency = $resolver->resolve($dependencyRequest);

        $this->assertSame(
            CorrectionRevalidationItemType::CandidateJustification,
            $justification->reviewableItems()[0]->classification,
        );
        $this->assertSame(
            CorrectionRevalidationItemType::DependencyAffected,
            $dependency->reviewableItems()[0]->classification,
        );
        $this->assertArrayHasKey(
            'text_hash',
            $justification->reviewableItems()[0]
                ->sourceSnapshot['response'],
        );
        $this->assertArrayNotHasKey(
            'text',
            $justification->reviewableItems()[0]
                ->sourceSnapshot['response'],
        );
    }

    public function test_manual_decision_blocks_final_snapshot(): void
    {
        [$request, $response] = $this->submittedRequest();
        $item = app(CorrectionDifferentialResolver::class)
            ->resolve($request)
            ->reviewableItems()[0];
        $response->forceFill([
            'status' => CorrectionResponseStatus::UnderReview,
            'review_result' => CorrectionResponseReviewResult::RequiresManualDecision,
            'reviewed_by' => $request->issued_by,
            'reviewed_at' => now('UTC')->startOfSecond(),
            'review_notes' => 'Requer validação hierárquica fictícia.',
            'differential_classification' => $item->classification,
            'decision_source_fingerprint' => $item->sourceFingerprint,
        ])->save();

        $differential = app(CorrectionDifferentialResolver::class)
            ->resolve($request);

        $this->expectException(ValidationException::class);
        app(CorrectionRevalidationSnapshotBuilder::class)->build(
            $differential,
            CorrectionRevalidationAggregateResult::RequiresManualDecision,
        );
    }

    public function test_non_submitted_request_cannot_be_resolved(): void
    {
        [$request] = $this->submittedRequest();
        $request->forceFill([
            'status' => CorrectionRequestStatus::Open,
        ])->save();

        $this->expectException(ValidationException::class);
        app(CorrectionDifferentialResolver::class)->resolve(
            $request->refresh(),
        );
    }

    public function test_tampered_original_snapshot_is_rejected(): void
    {
        [$request] = $this->submittedRequest();
        $publicationResult = $request->publicationResult()
            ->firstOrFail();
        $batchItem = $publicationResult->batchItem()
            ->firstOrFail();
        $payload = $batchItem->snapshot_payload;
        $payload['outcome'] = 'tampered';
        DB::table('application_review_batch_items')
            ->where('id', $batchItem->id)
            ->update([
                'snapshot_payload' => json_encode(
                    $payload,
                    JSON_THROW_ON_ERROR,
                ),
            ]);

        $this->expectException(ValidationException::class);
        app(CorrectionDifferentialResolver::class)->resolve(
            $request->refresh(),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $validatedSnapshots
     * @param  array<string, mixed>  $answerData
     * @return array{CorrectionRequest, CorrectionResponse, User}
     */
    private function submittedRequest(
        array $validatedSnapshots = [],
        array $answerData = [],
        bool $withDocument = true,
        ?\Closure $configureItem = null,
    ): array {
        $municipality = Municipality::factory()->create();
        $operator = $this->userWithRole('municipal_technician');
        $request = $this->createPublishedCorrectionRequest(
            municipality: $municipality,
            operator: $operator,
            status: CorrectionRequestStatus::Open,
            completedItems: 0,
            totalItems: 1,
            deadline: now()->addWeek(),
            validatedDocumentSnapshots: $validatedSnapshots,
        );
        $candidate = $request->candidate()->firstOrFail();
        $request->administrativeProcess()->update([
            'assigned_to' => $operator->id,
        ]);
        $item = $request->items()->firstOrFail();
        if ($configureItem instanceof \Closure) {
            $configureItem($item);
            $item->refresh();
        }
        $response = app(CandidateCorrectionWorkspaceService::class)
            ->save(
                request: $request->refresh(),
                item: $item,
                data: $answerData,
                file: $withDocument
                    ? UploadedFile::fake()->create(
                        'documento-teste.pdf',
                        32,
                        'application/pdf',
                    )
                    : null,
                candidate: $candidate,
            );
        app(CorrectionSubmissionService::class)->submit(
            $request->refresh(),
            $candidate,
        );
        $request = $request->refresh()->load([
            'submissionReceipt',
            'responses',
        ]);

        return [$request, $response->refresh(), $operator];
    }

    /** @return array<string, mixed> */
    private function validatedDocumentSnapshot(int $id): array
    {
        return [
            'id' => $id,
            'document_type_id' => 700001,
            'required_document_id' => 800001,
            'requirement_instance' => 1,
            'reference_period' => null,
            'status' => DocumentStatus::Validated->value,
            'checksum' => hash('sha256', 'validated-'.$id),
            'current_version_id' => $id + 1,
            'target' => [
                'application_id' => null,
            ],
            'submitted_at' => '2026-07-01T09:00:00+00:00',
            'reviewed_at' => '2026-07-01T10:00:00+00:00',
            'validated_at' => '2026-07-01T10:00:00+00:00',
            'rejected_at' => null,
            'rejection_reason' => null,
            'latest_review' => null,
        ];
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
