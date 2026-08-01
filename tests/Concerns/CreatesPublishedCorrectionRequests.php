<?php

namespace Tests\Concerns;

use App\Enums\AdministrativeProcessStatus;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\ApplicationReviewBatchStatus;
use App\Enums\ContestDeadlineType;
use App\Enums\CorrectionRequestItemStatus;
use App\Enums\CorrectionRequestStatus;
use App\Enums\DocumentAppliesTo;
use App\Enums\DocumentStatus;
use App\Enums\FeatureKey;
use App\Models\AdministrativeProcess;
use App\Models\ApplicationReviewBatch;
use App\Models\ApplicationReviewBatchItem;
use App\Models\ContestDeadline;
use App\Models\CorrectionRequest;
use App\Models\DocumentType;
use App\Models\Municipality;
use App\Models\RequiredDocument;
use App\Models\Role;
use App\Models\User;
use App\Services\Administrative\ApplicationReviewPublicationService;
use App\Services\Support\CanonicalJsonHasher;
use DateTimeInterface;
use RuntimeException;

trait CreatesPublishedCorrectionRequests
{
    /**
     * @param  list<array<string, mixed>>  $validatedDocumentSnapshots
     */
    protected function createPublishedCorrectionRequest(
        Municipality $municipality,
        User $operator,
        CorrectionRequestStatus $status,
        int $completedItems,
        int $totalItems,
        DateTimeInterface $deadline,
        array $validatedDocumentSnapshots = [],
    ): CorrectionRequest {
        if ($totalItems < 1) {
            throw new RuntimeException(
                'A fixture publicada exige pelo menos um item.',
            );
        }

        if (
            $completedItems < 0
            || $completedItems > $totalItems
        ) {
            throw new RuntimeException(
                'O número de itens concluídos é inválido.',
            );
        }

        $this->enableMunicipalityFeatures(
            $municipality,
            FeatureKey::ApplicationReview,
        );
        $this->assignMunicipality($operator, $municipality);

        $process = AdministrativeProcess::factory()->create();
        $application = $process->application()->firstOrFail();
        $candidate = $application->user()->firstOrFail();

        $application->forceFill([
            'user_id' => $candidate->id,
        ])->save();
        $application->program()->update([
            'municipality_id' => $municipality->id,
        ]);

        $candidate->forceFill([
            'municipality_id' => $municipality->id,
            'email_verified_at' => now(),
        ])->save();

        $candidateRole = Role::query()
            ->where('name', 'candidate')
            ->firstOrFail();

        $candidate->roles()->syncWithoutDetaching([
            $candidateRole->id,
        ]);

        $process->forceFill([
            'program_id' => $application->program_id,
            'contest_id' => $application->contest_id,
            'user_id' => $candidate->id,
            'status' => AdministrativeProcessStatus::DocumentReview,
        ])->save();

        ContestDeadline::query()->create([
            'contest_id' => $application->contest_id,
            'type' => ContestDeadlineType::Corrections,
            'label' => ContestDeadlineType::Corrections
                ->defaultLabel(),
            'starts_at' => now()->subMinute(),
            'ends_at' => $deadline,
            'sort_order' => 30,
        ]);

        $findings = [];

        for ($position = 1; $position <= $totalItems; $position++) {
            $documentType = DocumentType::factory()->create([
                'name' => 'Documento de aperfeiçoamento '
                    .$position,
                'applies_to' => DocumentAppliesTo::Application,
            ]);

            $requiredDocument = RequiredDocument::factory()
                ->create([
                    'document_type_id' => $documentType->id,
                    'program_id' => $application->program_id,
                    'contest_id' => $application->contest_id,
                    'required_for' => DocumentAppliesTo::Application,
                ]);

            $findings[] = [
                'finding_status' => 'missing',
                'document_status' => DocumentStatus::Missing->value,
                'target_type' => $application->getMorphClass(),
                'target_id' => $application->id,
                'target_label' => $application->application_number,
                'document_type_id' => $documentType->id,
                'required_document_id' => $requiredDocument->id,
                'source_document_submission_id' => null,
                'requirement_instance' => 1,
                'title' => $position === 1
                    ? 'Documento confidencial'
                    : 'Elemento '.$position,
                'description' => 'Submeta apenas o elemento solicitado.',
                'is_required' => true,
                'sort_order' => $position,
            ];
        }

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
            'outcome' => ApplicationReviewBatchOutcome::CorrectionRequired
                ->value,
            'technical_result' => 'requires_correction',
            'review' => null,
            'readiness' => [
                'ready' => false,
                'missing' => $totalItems,
                'blockers' => [
                    $totalItems
                    .' elemento(s) obrigatório(s) em falta',
                ],
            ],
            'documents' => $validatedDocumentSnapshots,
            'findings' => $findings,
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
            'municipality_id' => $municipality->id,
            'contest_id' => $application->contest_id,
            'cycle' => ApplicationReviewBatchCycle::InitialReview,
            'sequence_number' => random_int(1, 999999),
            'status' => ApplicationReviewBatchStatus::Sealed,
            'reason' => 'Lote selado para fixture operacional 53E-D.',
            'item_count' => 1,
            'seal_key' => hash(
                'sha256',
                'seal-53e-d-'.$process->id,
            ),
            'source_fingerprint' => hash(
                'sha256',
                'source-53e-d-'.$process->id,
            ),
            'snapshot_hash' => $batchHash,
        ]);

        $batch->forceFill([
            'sealed_by' => $operator->id,
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
            'document_snapshot' => $validatedDocumentSnapshots,
            'snapshot_payload' => $payload,
            'source_fingerprint' => hash(
                'sha256',
                'item-source-53e-d-'.$process->id,
            ),
            'snapshot_hash' => $snapshotHash,
        ]);

        $this->actingAs($operator);

        $service = app(
            ApplicationReviewPublicationService::class,
        );
        $reason =
            'Publicação canónica para fixture operacional 53E-D.';

        $preview = $service->preview(
            $batch->refresh()->load([
                'contest.program',
                'items',
            ]),
            $operator,
            $reason,
        );

        $service->publish(
            $batch->refresh()->load([
                'contest.program',
                'items',
            ]),
            $operator,
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
            ->with([
                'items',
                'publicationResult',
                'application',
                'administrativeProcess',
            ])
            ->firstOrFail();

        foreach (
            $request->items
                ->sortBy('sort_order')
                ->values() as $index => $item
        ) {
            $item->forceFill([
                'status' => $index < $completedItems
                    ? CorrectionRequestItemStatus::Responded
                    : CorrectionRequestItemStatus::Pending,
            ])->save();
        }

        $request->forceFill([
            'status' => $status,
            'response_deadline_at' => $deadline,
            'submitted_at' => $status === CorrectionRequestStatus::Submitted
                    ? now()
                    : null,
            'responded_at' => $status === CorrectionRequestStatus::Submitted
                    ? now()
                    : null,
            'expired_at' => $status === CorrectionRequestStatus::Expired
                    ? now()
                    : null,
        ])->save();

        $process->forceFill([
            'status' => match ($status) {
                CorrectionRequestStatus::Submitted => AdministrativeProcessStatus::CorrectionSubmitted,
                CorrectionRequestStatus::Expired => AdministrativeProcessStatus::CorrectionOverdue,
                default => AdministrativeProcessStatus::AwaitingCandidateResponse,
            },
        ])->save();

        return $request->refresh()->load([
            'items',
            'publicationResult',
            'application',
            'administrativeProcess',
        ]);
    }
}
