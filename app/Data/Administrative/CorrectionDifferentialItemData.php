<?php

namespace App\Data\Administrative;

use App\Enums\CorrectionResponseKind;
use App\Enums\CorrectionRevalidationItemType;

final readonly class CorrectionDifferentialItemData
{
    /**
     * @param  array<string, mixed>  $sourceSnapshot
     */
    public function __construct(
        public string $key,
        public CorrectionRevalidationItemType $classification,
        public ?int $correctionRequestItemId,
        public ?int $correctionResponseId,
        public ?int $sourceDocumentSubmissionId,
        public ?int $submittedDocumentSubmissionId,
        public ?int $originalDocumentVersionId,
        public ?int $submittedDocumentVersionId,
        public ?int $requiredDocumentId,
        public int $requirementInstance,
        public ?string $targetType,
        public ?int $targetId,
        public ?string $originalChecksum,
        public ?string $submittedChecksum,
        public ?CorrectionResponseKind $responseKind,
        public bool $stale,
        public string $sourceFingerprint,
        public array $sourceSnapshot,
    ) {}

    public function isReviewable(): bool
    {
        return $this->classification->isReviewable();
    }

    /** @return array<string, mixed> */
    public function fingerprintPayload(): array
    {
        return [
            'key' => $this->key,
            'classification' => $this->classification->value,
            'correction_request_item_id' => $this->correctionRequestItemId,
            'correction_response_id' => $this->correctionResponseId,
            'source_document_submission_id' => $this->sourceDocumentSubmissionId,
            'submitted_document_submission_id' => $this->submittedDocumentSubmissionId,
            'original_document_version_id' => $this->originalDocumentVersionId,
            'submitted_document_version_id' => $this->submittedDocumentVersionId,
            'required_document_id' => $this->requiredDocumentId,
            'requirement_instance' => $this->requirementInstance,
            'target_type' => $this->targetType,
            'target_id' => $this->targetId,
            'original_checksum' => $this->originalChecksum,
            'submitted_checksum' => $this->submittedChecksum,
            'response_kind' => $this->responseKind?->value,
            'stale' => $this->stale,
            'source_fingerprint' => $this->sourceFingerprint,
        ];
    }
}
