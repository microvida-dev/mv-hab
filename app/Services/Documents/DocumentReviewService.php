<?php

namespace App\Services\Documents;

use App\Enums\DocumentAccessAction;
use App\Enums\DocumentReviewDecision;
use App\Enums\DocumentStatus;
use App\Models\DocumentReview;
use App\Models\DocumentSubmission;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;

class DocumentReviewService
{
    public function __construct(
        private readonly DocumentAccessService $accessService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function markUnderReview(DocumentSubmission $submission, User $actor, ?string $internalNotes = null): DocumentSubmission
    {
        return $this->transition(
            submission: $submission,
            actor: $actor,
            toStatus: DocumentStatus::UnderReview,
            decision: DocumentReviewDecision::UnderReview,
            reason: null,
            internalNotes: $internalNotes,
            auditAction: 'under_review',
        );
    }

    public function validate(DocumentSubmission $submission, User $actor, ?string $internalNotes = null): DocumentSubmission
    {
        return $this->transition(
            submission: $submission,
            actor: $actor,
            toStatus: DocumentStatus::Validated,
            decision: DocumentReviewDecision::Validated,
            reason: null,
            internalNotes: $internalNotes,
            auditAction: 'validate',
        );
    }

    public function reject(DocumentSubmission $submission, User $actor, string $reason, ?string $internalNotes = null): DocumentSubmission
    {
        return $this->transition(
            submission: $submission,
            actor: $actor,
            toStatus: DocumentStatus::Rejected,
            decision: DocumentReviewDecision::Rejected,
            reason: $reason,
            internalNotes: $internalNotes,
            auditAction: 'reject',
        );
    }

    private function transition(
        DocumentSubmission $submission,
        User $actor,
        DocumentStatus $toStatus,
        DocumentReviewDecision $decision,
        ?string $reason,
        ?string $internalNotes,
        string $auditAction,
    ): DocumentSubmission {
        $result = DB::transaction(function () use ($submission, $actor, $toStatus, $decision, $reason, $internalNotes, $auditAction) {
            $fromStatus = $submission->status;

            $review = new DocumentReview([
                'reason' => $reason,
                'internal_notes' => $internalNotes,
            ]);
            $review->forceFill([
                'document_submission_id' => $submission->id,
                'reviewed_by' => $actor->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'decision' => $decision,
            ]);
            $review->save();

            $submission->forceFill([
                'status' => $toStatus,
                'reviewed_at' => now(),
                'reviewed_by' => $actor->id,
                'validated_at' => $toStatus === DocumentStatus::Validated ? now() : null,
                'validated_by' => $toStatus === DocumentStatus::Validated ? $actor->id : null,
                'rejected_at' => $toStatus === DocumentStatus::Rejected ? now() : null,
                'rejected_by' => $toStatus === DocumentStatus::Rejected ? $actor->id : null,
                'rejection_reason' => $toStatus === DocumentStatus::Rejected ? $reason : null,
            ]);
            $submission->save();

            $this->accessService->record(
                $submission,
                $toStatus === DocumentStatus::Rejected ? DocumentAccessAction::Reject : DocumentAccessAction::Validate,
                $submission->currentVersion,
                $actor,
            );

            $this->auditLogger->record(
                event: $toStatus === DocumentStatus::Rejected ? AuditEvents::REJECT : AuditEvents::APPROVE,
                auditable: $submission,
                module: 'documents',
                action: $auditAction,
                description: 'Estado documental alterado por utilizador autorizado.',
                metadata: [
                    'actor_id' => $actor->id,
                    'from_status' => $fromStatus?->value,
                    'to_status' => $toStatus->value,
                    'review_id' => $review->id,
                ],
            );

            return $submission->fresh(['documentType', 'requiredDocument', 'currentVersion', 'reviews']);
        });

        assert($result instanceof DocumentSubmission);

        return $result;
    }
}
