<?php

namespace Tests\Feature;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\ApplicationReviewBatchStatus;
use App\Enums\ContestDeadlineType;
use App\Enums\CorrectionRequestStatus;
use App\Enums\CorrectionResponseKind;
use App\Enums\CorrectionResponseStatus;
use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentStatus;
use App\Enums\FeatureKey;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewBatchItem;
use App\Models\ContestDeadline;
use App\Models\CorrectionRequest;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\Permission;
use App\Models\RequiredDocument;
use App\Models\Role;
use App\Models\User;
use App\Services\Administrative\ApplicationReviewPublicationService;
use App\Services\Support\CanonicalJsonHasher;
use Database\Seeders\SystemAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithMunicipalFeatures;
use Tests\TestCase;

class Sprint53ECandidateCorrectionWorkspaceTest extends TestCase
{
    use InteractsWithMunicipalFeatures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccessSeeder::class);
        Storage::fake('local');
        Queue::fake();
    }

    public function test_candidate_saves_a_justification_without_municipal_notification(): void
    {
        [$request, $candidate] = $this->publishedCorrectionRequest(
            documentStatus: DocumentStatus::Missing,
        );
        $item = $request->items()->firstOrFail();
        $notificationsBefore = $candidate->officialNotifications()->count();

        $this->actingAs($candidate)
            ->post(
                route(
                    'candidate.correction-requests.responses.store',
                    $request,
                ),
                [
                    'correction_request_item_id' => $item->id,
                    'justification' => 'O documento ainda não foi emitido pela entidade competente.',
                ],
            )
            ->assertRedirect(
                route(
                    'candidate.correction-requests.show',
                    $request,
                ),
            );

        $this->assertDatabaseHas('correction_responses', [
            'correction_request_id' => $request->id,
            'correction_request_item_id' => $item->id,
            'user_id' => $candidate->id,
            'response_kind' => CorrectionResponseKind::Justification->value,
            'status' => CorrectionResponseStatus::Draft->value,
            'document_submission_id' => null,
            'submitted_at' => null,
        ]);
        $this->assertNotNull(
            $request->responses()->firstOrFail()->prepared_at,
        );
        $this->assertSame(
            CorrectionRequestStatus::PartiallyCompleted,
            $request->refresh()->status,
        );
        $this->assertSame(
            $notificationsBefore,
            $candidate->officialNotifications()->count(),
        );
    }

    public function test_replacement_preserves_original_and_links_new_version(): void
    {
        [$request, $candidate, $application, $requiredDocument] =
            $this->publishedCorrectionRequest(
                documentStatus: DocumentStatus::Rejected,
                createSourceDocument: true,
            );

        $item = $request->items()
            ->with('sourceDocumentSubmission.currentVersion')
            ->firstOrFail();
        $source = $item->sourceDocumentSubmission;
        $originalVersion = $source?->currentVersion;

        $this->assertInstanceOf(
            DocumentSubmission::class,
            $source,
        );
        $this->assertInstanceOf(
            DocumentVersion::class,
            $originalVersion,
        );

        $this->actingAs($candidate)
            ->post(
                route(
                    'candidate.correction-requests.responses.store',
                    $request,
                ),
                [
                    'correction_request_item_id' => $item->id,
                    'file' => UploadedFile::fake()->create(
                        'documento-corrigido.pdf',
                        64,
                        'application/pdf',
                    ),
                ],
            )
            ->assertRedirect(
                route(
                    'candidate.correction-requests.show',
                    $request,
                ),
            );

        $source?->refresh()->load('currentVersion');
        $currentVersion = $source?->currentVersion;

        $this->assertInstanceOf(
            DocumentVersion::class,
            $currentVersion,
        );
        $this->assertNotSame(
            $originalVersion?->id,
            $currentVersion->id,
        );
        $this->assertSame(
            $originalVersion?->id,
            $currentVersion->replaces_document_version_id,
        );
        $this->assertDatabaseHas('document_versions', [
            'id' => $originalVersion?->id,
            'document_submission_id' => $source?->id,
        ]);
        $this->assertDatabaseHas('correction_responses', [
            'correction_request_id' => $request->id,
            'correction_request_item_id' => $item->id,
            'application_id' => $application->id,
            'user_id' => $candidate->id,
            'document_submission_id' => $source?->id,
            'document_version_id' => $currentVersion->id,
            'response_kind' => CorrectionResponseKind::Document->value,
            'status' => CorrectionResponseStatus::Draft->value,
        ]);
        $this->assertSame(
            $requiredDocument->id,
            $source?->required_document_id,
        );
    }

    public function test_candidate_cannot_prepare_an_item_from_another_request(): void
    {
        [$firstRequest, $candidate] =
            $this->publishedCorrectionRequest(
                documentStatus: DocumentStatus::Missing,
            );
        [$secondRequest] = $this->publishedCorrectionRequest(
            documentStatus: DocumentStatus::Missing,
            candidate: $candidate,
        );
        $foreignItem = $secondRequest->items()->firstOrFail();

        $this->actingAs($candidate)
            ->from(
                route(
                    'candidate.correction-requests.show',
                    $firstRequest,
                ),
            )
            ->post(
                route(
                    'candidate.correction-requests.responses.store',
                    $firstRequest,
                ),
                [
                    'correction_request_item_id' => $foreignItem->id,
                    'justification' => 'Justificação suficientemente detalhada para o teste.',
                ],
            )
            ->assertRedirect(
                route(
                    'candidate.correction-requests.show',
                    $firstRequest,
                ),
            )
            ->assertSessionHasErrors(
                'correction_request_item_id',
            );

        $this->assertDatabaseMissing('correction_responses', [
            'correction_request_id' => $firstRequest->id,
            'correction_request_item_id' => $foreignItem->id,
        ]);
    }

    /**
     * @return array{
     *     CorrectionRequest,
     *     User,
     *     Application,
     *     RequiredDocument
     * }
     */
    private function publishedCorrectionRequest(
        DocumentStatus $documentStatus,
        bool $createSourceDocument = false,
        ?User $candidate = null,
    ): array {
        $process = AdministrativeProcess::factory()->create();
        $application = $process->application()->firstOrFail();
        $candidate ??= $application->user;

        if ((int) $application->user_id !== (int) $candidate->id) {
            $application->forceFill([
                'user_id' => $candidate->id,
            ])->save();
            $process->forceFill([
                'user_id' => $candidate->id,
            ])->save();
        }

        $actor = $this->userWithPermissions([
            'administrative_processes.view',
            'administrative_processes.publish',
        ]);
        $this->assignApplicationMunicipality(
            $actor,
            $application,
            FeatureKey::ApplicationReview,
        );
        $candidate->forceFill([
            'municipality_id' => $actor->municipality_id,
            'email_verified_at' => now(),
        ])->save();
        $process->forceFill([
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
            'user_id' => $candidate->id,
            'status' => AdministrativeProcessStatus::DocumentReview,
        ])->save();

        $candidateRole = Role::query()
            ->where('name', 'candidate')
            ->firstOrFail();
        $candidate->roles()->syncWithoutDetaching([
            $candidateRole->id,
        ]);

        $documentType = DocumentType::factory()->create([
            'applies_to' => DocumentAppliesTo::Application,
        ]);
        $requiredDocument = RequiredDocument::factory()->create([
            'document_type_id' => $documentType->id,
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
            'required_for' => DocumentAppliesTo::Application,
        ]);

        $source = null;

        if ($createSourceDocument) {
            $source = DocumentSubmission::factory()
                ->forRequiredDocument($requiredDocument)
                ->create([
                    'user_id' => $candidate->id,
                    'adhesion_registration_id' => $application->adhesion_registration_id,
                    'application_id' => $application->id,
                    'status' => $documentStatus,
                    'submitted_by' => $candidate->id,
                ]);
            $originalVersion = new DocumentVersion;
            $originalVersion->forceFill([
                'document_submission_id' => $source->id,
                'version_number' => 1,
                'original_filename' => 'documento-original.pdf',
                'stored_filename' => 'documento-original.pdf',
                'storage_disk' => 'local',
                'storage_path' => 'documents/test/documento-original.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'checksum' => hash('sha256', 'original-'.$source->id),
                'uploaded_by' => $candidate->id,
                'uploaded_at' => now(),
                'status_at_upload' => $documentStatus,
            ])->save();
            $source->forceFill([
                'current_version_id' => $originalVersion->id,
            ])->save();
        }

        $deadline = ContestDeadline::query()->create([
            'contest_id' => $application->contest_id,
            'type' => ContestDeadlineType::Corrections,
            'label' => ContestDeadlineType::Corrections->defaultLabel(),
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDays(5),
            'sort_order' => 30,
        ]);

        $payload = [
            'schema_version' => 2,
            'process' => [
                'id' => $process->id,
                'number' => $process->process_number,
                'status' => $process->status->value,
                'assigned_to' => null,
                'application_id' => $application->id,
                'contest_id' => $application->contest_id,
                'program_id' => $application->program_id,
            ],
            'application' => [
                'id' => $application->id,
                'public_id' => $application->public_id,
                'number' => $application->application_number,
                'status' => $application->status->value,
                'submitted_at' => null,
                'program_id' => $application->program_id,
                'contest_id' => $application->contest_id,
                'legal_regime' => null,
                'regulatory_snapshot_id' => null,
            ],
            'outcome' => ApplicationReviewBatchOutcome::CorrectionRequired->value,
            'technical_result' => 'requires_correction',
            'review' => null,
            'readiness' => [
                'ready' => false,
                'missing' => 1,
                'blockers' => ['1 documento obrigatório em falta'],
            ],
            'documents' => [],
            'findings' => [[
                'finding_status' => match ($documentStatus) {
                    DocumentStatus::Missing => 'missing',
                    DocumentStatus::Rejected => 'invalid',
                    DocumentStatus::Expired => 'expired',
                    default => 'missing',
                },
                'document_status' => $documentStatus->value,
                'target_type' => $application->getMorphClass(),
                'target_id' => $application->id,
                'target_label' => $application->application_number,
                'document_type_id' => $documentType->id,
                'required_document_id' => $requiredDocument->id,
                'source_document_submission_id' => $source?->id,
                'requirement_instance' => 1,
                'title' => $documentType->name,
                'description' => 'Submeta o documento solicitado.',
                'is_required' => true,
                'sort_order' => 1,
            ]],
        ];

        $hasher = app(CanonicalJsonHasher::class);
        $snapshotHash = $hasher->hash($payload);
        $batchHash = $hasher->hash([
            'schema_version' => 1,
            'contest_id' => $application->contest_id,
            'cycle' => ApplicationReviewBatchCycle::InitialReview->value,
            'items' => [[
                'application_id' => $application->id,
                'snapshot_hash' => $snapshotHash,
                'payload' => $payload,
            ]],
        ]);

        $batch = new ApplicationReviewBatch([
            'municipality_id' => $actor->municipality_id,
            'contest_id' => $application->contest_id,
            'cycle' => ApplicationReviewBatchCycle::InitialReview,
            'sequence_number' => random_int(1, 999999),
            'status' => ApplicationReviewBatchStatus::Sealed,
            'reason' => 'Lote selado para workspace do candidato.',
            'item_count' => 1,
            'seal_key' => hash(
                'sha256',
                'seal-'.$process->id,
            ),
            'source_fingerprint' => hash(
                'sha256',
                'source-'.$process->id,
            ),
            'snapshot_hash' => $batchHash,
        ]);
        $batch->forceFill([
            'sealed_by' => $actor->id,
            'sealed_at' => now(),
        ])->save();

        ApplicationReviewBatchItem::query()->create([
            'application_review_batch_id' => $batch->id,
            'administrative_process_id' => $process->id,
            'application_id' => $application->id,
            'application_review_id' => null,
            'process_number' => $process->process_number,
            'application_number' => $application->application_number,
            'application_public_id' => $application->public_id,
            'outcome' => ApplicationReviewBatchOutcome::CorrectionRequired,
            'technical_result' => 'requires_correction',
            'review_lock_version' => 1,
            'readiness_snapshot' => $payload['readiness'],
            'document_snapshot' => [],
            'snapshot_payload' => $payload,
            'source_fingerprint' => hash(
                'sha256',
                'item-source-'.$process->id,
            ),
            'snapshot_hash' => $snapshotHash,
        ]);

        $this->actingAs($actor);
        $service = app(ApplicationReviewPublicationService::class);
        $reason = 'Publicação inicial para aperfeiçoamento.';
        $preview = $service->preview(
            $batch->refresh()->load(['contest.program', 'items']),
            $actor,
            $reason,
        );
        $publication = $service->publish(
            $batch->refresh()->load(['contest.program', 'items']),
            $actor,
            [
                'reason' => $reason,
                'preview_token' => $preview['token'],
            ],
        );

        $request = CorrectionRequest::query()
            ->where('application_id', $application->id)
            ->whereNotNull(
                'application_review_publication_result_id',
            )
            ->with('items')
            ->firstOrFail();

        $this->assertSame(
            $deadline->ends_at->toDateTimeString(),
            $request->response_deadline_at?->toDateTimeString(),
        );
        $this->assertNotNull($publication->published_at);

        return [
            $request,
            $candidate->refresh(),
            $application->refresh(),
            $requiredDocument,
        ];
    }

    /** @param list<string> $permissions */
    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $role = Role::query()->create([
            'name' => 'correction_workspace_'.str()->random(8),
            'label' => 'Correction workspace test role',
            'scope' => 'municipal',
            'is_system' => false,
        ]);
        $permissionIds = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('id');

        $this->assertCount(
            count($permissions),
            $permissionIds,
        );

        $role->permissions()->sync($permissionIds);
        $user->roles()->attach($role);

        return $user;
    }
}
