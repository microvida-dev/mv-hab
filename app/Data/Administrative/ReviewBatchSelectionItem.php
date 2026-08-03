<?php

namespace App\Data\Administrative;

use App\Enums\ApplicationReviewBatchOutcome;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReview;
use App\Models\DocumentSubmission;
use Illuminate\Support\Collection;

final readonly class ReviewBatchSelectionItem
{
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
     * @param  array<string, mixed>  $snapshotPayload
     */
    public function __construct(
        public AdministrativeProcess $process,
        public Application $application,
        public ?ApplicationReview $review,
        public ApplicationReviewBatchOutcome $outcome,
        public array $readiness,
        public Collection $documents,
        public array $snapshotPayload,
        public string $sourceFingerprint,
        public string $snapshotHash,
    ) {}
}
