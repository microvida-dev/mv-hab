<?php

namespace App\Services\Administrative;

use App\Data\Administrative\CorrectionDifferentialItemData;
use App\Data\Administrative\CorrectionDifferentialResultData;
use App\Enums\ApplicationReviewBatchCycle;
use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\ApplicationReviewResult;
use App\Enums\CorrectionResponseReviewResult;
use App\Enums\CorrectionRevalidationAggregateResult;
use App\Enums\CorrectionRevalidationItemType;
use App\Models\CorrectionResponse;
use App\Services\Support\CanonicalJsonHasher;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

final class CorrectionRevalidationSnapshotBuilder
{
    public function __construct(
        private readonly CanonicalJsonHasher $hasher,
    ) {}

    /**
     * @return array{
     *     payload: array<string, mixed>,
     *     snapshot_hash: string,
     *     outcome: ApplicationReviewBatchOutcome,
     *     technical_result: ApplicationReviewResult
     * }
     */
    public function build(
        CorrectionDifferentialResultData $differential,
        CorrectionRevalidationAggregateResult $aggregateResult,
    ): array {
        if ($differential->isStale()) {
            throw ValidationException::withMessages([
                'revalidation' => 'As fontes da segunda análise foram alteradas. Reabra a análise a partir das fontes autoritativas.',
            ]);
        }

        $responses = $differential->request->responses->keyBy('id');
        $decisions = [];

        foreach ($differential->reviewableItems() as $item) {
            $response = $responses->get($item->correctionResponseId);

            if (! $response instanceof CorrectionResponse) {
                throw ValidationException::withMessages([
                    'revalidation' => 'Um elemento reavaliável não possui resposta persistida.',
                ]);
            }

            if (
                ! $response->review_result instanceof CorrectionResponseReviewResult
                || $response->reviewed_at === null
                || $response->reviewed_by === null
                || ! is_string($response->decision_source_fingerprint)
                || ! hash_equals(
                    $item->sourceFingerprint,
                    $response->decision_source_fingerprint,
                )
            ) {
                throw ValidationException::withMessages([
                    'revalidation' => 'Um elemento reavaliável não possui decisão válida para a fonte atual.',
                ]);
            }

            $decisions[] = [
                'key' => $item->key,
                'correction_request_item_id' => $item->correctionRequestItemId,
                'correction_response_id' => $item->correctionResponseId,
                'classification' => $item->classification->value,
                'result' => $response->review_result->value,
                'reviewed_by' => (int) $response->reviewed_by,
                'reviewed_at' => $this->dateTime($response->reviewed_at),
                'review_notes' => $response->review_notes,
                'source_fingerprint' => $item->sourceFingerprint,
            ];
        }

        usort(
            $decisions,
            static fn (array $left, array $right): int => (
                (string) $left['key']
            ) <=> ((string) $right['key']),
        );

        [$outcome, $technicalResult] = match ($aggregateResult) {
            CorrectionRevalidationAggregateResult::Accepted => [
                ApplicationReviewBatchOutcome::CompletePendingDecision,
                ApplicationReviewResult::Passed,
            ],
            CorrectionRevalidationAggregateResult::Rejected => [
                ApplicationReviewBatchOutcome::CorrectionRejected,
                ApplicationReviewResult::Failed,
            ],
            CorrectionRevalidationAggregateResult::RequiresManualDecision => throw ValidationException::withMessages([
                'revalidation' => 'Uma decisão manual pendente impede a selagem da segunda análise.',
            ]),
        };

        $carriedForward = $this->snapshotItems(
            $differential->carriedForwardItems(),
        );
        $changed = $this->snapshotItemsByType(
            $differential->items,
            [
                CorrectionRevalidationItemType::ChangedDocument,
                CorrectionRevalidationItemType::NewDocument,
                CorrectionRevalidationItemType::ReplacedDocument,
            ],
        );
        $justifications = $this->snapshotItemsByType(
            $differential->items,
            [CorrectionRevalidationItemType::CandidateJustification],
        );
        $dependencies = $this->snapshotItemsByType(
            $differential->items,
            [CorrectionRevalidationItemType::DependencyAffected],
        );
        $documents = array_values(array_filter(array_map(
            static fn (CorrectionDifferentialItemData $item): ?array => (
                $item->sourceDocumentSubmissionId !== null
                || $item->submittedDocumentSubmissionId !== null
            ) ? [
                'key' => $item->key,
                'classification' => $item->classification->value,
                'source_document_submission_id' => $item->sourceDocumentSubmissionId,
                'submitted_document_submission_id' => $item->submittedDocumentSubmissionId,
                'original_document_version_id' => $item->originalDocumentVersionId,
                'submitted_document_version_id' => $item->submittedDocumentVersionId,
                'original_checksum' => $item->originalChecksum,
                'submitted_checksum' => $item->submittedChecksum,
            ] : null,
            $differential->items,
        )));
        usort(
            $documents,
            static fn (array $left, array $right): int => (
                (string) $left['key']
            ) <=> ((string) $right['key']),
        );

        $payload = [
            'schema_version' => 1,
            'cycle' => ApplicationReviewBatchCycle::Revalidation->value,
            'process' => [
                'id' => (int) $differential->process->id,
                'number' => (string) $differential->process->process_number,
                'status' => $differential->process->status->value,
                'application_id' => (int) $differential->process->application_id,
                'contest_id' => $differential->process->contest_id,
                'program_id' => $differential->process->program_id,
            ],
            'application' => [
                'id' => (int) $differential->application->id,
                'public_id' => (string) $differential->application->public_id,
                'number' => $differential->application->application_number,
                'status' => $differential->application->status->value,
                'program_id' => (int) $differential->application->program_id,
                'contest_id' => (int) $differential->application->contest_id,
            ],
            'outcome' => $outcome->value,
            'technical_result' => $technicalResult->value,
            'review' => null,
            'readiness' => [
                'ready' => true,
                'carried_forward' => count($carriedForward),
                'reviewed' => count($decisions),
                'rejected' => count(array_filter(
                    $decisions,
                    static fn (array $decision): bool => $decision['result']
                        === CorrectionResponseReviewResult::Rejected->value,
                )),
                'blockers' => [],
            ],
            'documents' => $documents,
            'findings' => [],
            'correction_request' => [
                'id' => (int) $differential->request->id,
                'number' => $differential->request->request_number,
                'source_snapshot_hash' => $differential->request->source_snapshot_hash,
                'submitted_at' => $this->dateTime(
                    $differential->request->submitted_at,
                ),
            ],
            'original_publication_result' => [
                'id' => (int) $differential->originalPublicationResult->id,
                'public_id' => $differential->originalPublicationResult->public_id,
                'source_snapshot_hash' => $differential->originalPublicationResult->source_snapshot_hash,
                'result_hash' => $differential->originalPublicationResult->result_hash,
                'published_at' => $this->dateTime(
                    $differential->originalPublicationResult->published_at,
                ),
            ],
            'submission_receipt' => [
                'id' => (int) $differential->submissionReceipt->id,
                'number' => $differential->submissionReceipt->receipt_number,
                'snapshot_hash' => $differential->submissionReceipt->snapshot_hash,
                'submitted_at' => $this->dateTime(
                    $differential->submissionReceipt->submitted_at,
                ),
            ],
            'carried_forward_items' => $carriedForward,
            'changed_items' => $changed,
            'justification_items' => $justifications,
            'dependency_affected_items' => $dependencies,
            'document_versions' => $documents,
            'decisions' => $decisions,
            'aggregate_result' => [
                'value' => $aggregateResult->value,
                'label' => $aggregateResult->label(),
            ],
            'source_fingerprint' => $differential->sourceFingerprint,
        ];

        return [
            'payload' => $payload,
            'snapshot_hash' => $this->hasher->hash($payload),
            'outcome' => $outcome,
            'technical_result' => $technicalResult,
        ];
    }

    /**
     * @param  list<CorrectionDifferentialItemData>  $items
     * @return list<array<string, mixed>>
     */
    private function snapshotItems(array $items): array
    {
        $payload = array_map(
            static fn (CorrectionDifferentialItemData $item): array => [
                'key' => $item->key,
                'classification' => $item->classification->value,
                'correction_request_item_id' => $item->correctionRequestItemId,
                'correction_response_id' => $item->correctionResponseId,
                'source_document_submission_id' => $item->sourceDocumentSubmissionId,
                'submitted_document_submission_id' => $item->submittedDocumentSubmissionId,
                'original_document_version_id' => $item->originalDocumentVersionId,
                'submitted_document_version_id' => $item->submittedDocumentVersionId,
                'required_document_id' => $item->requiredDocumentId,
                'requirement_instance' => $item->requirementInstance,
                'target_type' => $item->targetType,
                'target_id' => $item->targetId,
                'original_checksum' => $item->originalChecksum,
                'submitted_checksum' => $item->submittedChecksum,
                'response_kind' => $item->responseKind?->value,
                'source_fingerprint' => $item->sourceFingerprint,
            ],
            $items,
        );
        usort(
            $payload,
            static fn (array $left, array $right): int => (
                (string) $left['key']
            ) <=> ((string) $right['key']),
        );

        return $payload;
    }

    /**
     * @param  list<CorrectionDifferentialItemData>  $items
     * @param  list<CorrectionRevalidationItemType>  $types
     * @return list<array<string, mixed>>
     */
    private function snapshotItemsByType(
        array $items,
        array $types,
    ): array {
        return $this->snapshotItems(array_values(array_filter(
            $items,
            static fn (CorrectionDifferentialItemData $item): bool => in_array(
                $item->classification,
                $types,
                true,
            ),
        )));
    }

    private function dateTime(mixed $value): ?string
    {
        return $value instanceof CarbonInterface
            ? $value->toIso8601String()
            : null;
    }
}
