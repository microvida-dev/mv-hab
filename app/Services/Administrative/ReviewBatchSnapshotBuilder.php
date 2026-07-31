<?php

namespace App\Services\Administrative;

use App\Enums\ApplicationReviewBatchOutcome;
use App\Enums\ApplicationReviewResult;
use App\Enums\ApplicationReviewStatus;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReview;
use App\Models\DocumentReview;
use App\Models\DocumentSubmission;
use App\Services\Support\CanonicalJsonHasher;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ReviewBatchSnapshotBuilder
{
    public function __construct(
        private readonly CanonicalJsonHasher $hasher,
    ) {}

    /**
     * @param  array{
     *     ready: bool,
     *     total_required: int,
     *     validated: int,
     *     submitted: int,
     *     under_review: int,
     *     missing: int,
     *     rejected: int,
     *     expired: int,
     *     blockers: list<string>
     * }  $readiness
     * @param  Collection<int, DocumentSubmission>  $documents
     * @return array{
     *     payload: array<string, mixed>,
     *     document_snapshot: list<array<string, mixed>>,
     *     source_fingerprint: string,
     *     snapshot_hash: string
     * }
     */
    public function build(
        AdministrativeProcess $process,
        Application $application,
        ?ApplicationReview $review,
        ApplicationReviewBatchOutcome $outcome,
        array $readiness,
        Collection $documents,
    ): array {
        $documentSnapshot = array_values(
            $documents
                ->sortBy('id')
                ->values()
                ->map(fn (DocumentSubmission $document): array => $this
                    ->documentPayload($document))
                ->all(),
        );

        $technicalResult = $this->technicalResult($outcome);
        $reviewPayload = $review instanceof ApplicationReview
            ? [
                'id' => (int) $review->id,
                'type' => $review->review_type->value,
                'status' => ApplicationReviewStatus::Completed->value,
                'result' => $technicalResult->value,
                'reviewed_by' => $review->reviewed_by,
                'ready_for_closure_at' => $this->dateTime(
                    $review->ready_for_closure_at,
                ),
                'ready_for_closure_by' => $review->ready_for_closure_by,
                'last_activity_at' => $this->dateTime(
                    $review->last_activity_at,
                ),
                'source_lock_version' => (int) $review->lock_version,
                'sealed_lock_version' => (int) $review->lock_version + 1,
                'summary' => $review->summary,
                'internal_notes' => $review->internal_notes,
            ]
            : null;

        $payload = [
            'schema_version' => 1,
            'process' => [
                'id' => (int) $process->id,
                'number' => (string) $process->process_number,
                'status' => $process->status->value,
                'assigned_to' => $process->assigned_to,
                'application_id' => (int) $process->application_id,
                'contest_id' => $process->contest_id,
                'program_id' => $process->program_id,
            ],
            'application' => [
                'id' => (int) $application->id,
                'public_id' => (string) $application->public_id,
                'number' => $application->application_number,
                'status' => $application->status->value,
                'submitted_at' => $this->dateTime(
                    $application->submitted_at,
                ),
                'program_id' => (int) $application->program_id,
                'contest_id' => (int) $application->contest_id,
                'legal_regime' => $application->legal_regime?->value,
                'regulatory_snapshot_id' => $application
                    ->regulatory_snapshot_id,
            ],
            'outcome' => $outcome->value,
            'technical_result' => $technicalResult->value,
            'review' => $reviewPayload,
            'readiness' => $readiness,
            'documents' => $documentSnapshot,
        ];

        $sourceState = [
            'process' => $this->hasher->modelState($process),
            'application' => $this->hasher->modelState($application),
            'review' => $review instanceof ApplicationReview
                ? $this->hasher->modelState($review)
                : null,
            'documents' => $documents
                ->sortBy('id')
                ->values()
                ->map(fn (DocumentSubmission $document): array => [
                    'id' => (int) $document->id,
                    'state' => $this->hasher->modelState($document),
                    'reviews' => $document->reviews
                        ->sortBy('id')
                        ->values()
                        ->map(fn (DocumentReview $documentReview): array => [
                            'id' => (int) $documentReview->id,
                            'state' => $this->hasher->modelState(
                                $documentReview,
                            ),
                        ])
                        ->all(),
                ])
                ->all(),
            'readiness' => $readiness,
            'outcome' => $outcome->value,
        ];

        return [
            'payload' => $payload,
            'document_snapshot' => $documentSnapshot,
            'source_fingerprint' => $this->hasher->hash($sourceState),
            'snapshot_hash' => $this->hasher->hash($payload),
        ];
    }

    /** @return array<string, mixed> */
    private function documentPayload(DocumentSubmission $document): array
    {
        /** @var DocumentReview|null $latestReview */
        $latestReview = $document->reviews
            ->sortByDesc('id')
            ->first();

        return [
            'id' => (int) $document->id,
            'document_type_id' => (int) $document->document_type_id,
            'required_document_id' => $document->required_document_id,
            'requirement_instance' => (int) $document
                ->requirement_instance,
            'reference_period' => $document->reference_period?->toDateString(),
            'status' => $document->status->value,
            'checksum' => $document->checksum,
            'current_version_id' => $document->current_version_id,
            'target' => [
                'adhesion_registration_id' => $document
                    ->adhesion_registration_id,
                'household_id' => $document->household_id,
                'household_member_id' => $document->household_member_id,
                'income_record_id' => $document->income_record_id,
                'current_housing_situation_id' => $document
                    ->current_housing_situation_id,
                'application_id' => $document->application_id,
                'contract_id' => $document->contract_id,
            ],
            'submitted_at' => $this->dateTime($document->submitted_at),
            'reviewed_at' => $this->dateTime($document->reviewed_at),
            'validated_at' => $this->dateTime($document->validated_at),
            'rejected_at' => $this->dateTime($document->rejected_at),
            'rejection_reason' => $document->rejection_reason,
            'latest_review' => $latestReview instanceof DocumentReview
                ? [
                    'id' => (int) $latestReview->id,
                    'from_status' => $this->enumValue(
                        $latestReview->from_status,
                    ),
                    'to_status' => $this->enumValue(
                        $latestReview->to_status,
                    ),
                    'decision' => $this->enumValue(
                        $latestReview->decision,
                    ),
                    'reviewed_by' => $latestReview->reviewed_by,
                    'reason' => $latestReview->reason,
                    'internal_notes' => $latestReview->internal_notes,
                    'created_at' => $this->dateTime(
                        $latestReview->created_at,
                    ),
                ]
                : null,
        ];
    }

    private function technicalResult(
        ApplicationReviewBatchOutcome $outcome,
    ): ApplicationReviewResult {
        return match ($outcome) {
            ApplicationReviewBatchOutcome::CompletePendingDecision => ApplicationReviewResult::Passed,
            ApplicationReviewBatchOutcome::CorrectionRequired => ApplicationReviewResult::RequiresCorrection,
            ApplicationReviewBatchOutcome::Withdrawn,
            ApplicationReviewBatchOutcome::NotAssessed => ApplicationReviewResult::NotApplicable,
        };
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }

    private function dateTime(mixed $value): ?string
    {
        return $value instanceof CarbonInterface
            ? $value->toIso8601String()
            : null;
    }
}
