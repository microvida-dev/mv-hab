<?php

namespace App\Data\Administrative;

use App\Models\AdministrativeProcess;
use App\Models\Application;
use App\Models\ApplicationReviewPublicationResult;
use App\Models\CorrectionRequest;
use App\Models\CorrectionSubmissionReceipt;

final readonly class CorrectionDifferentialResultData
{
    /**
     * @param  list<CorrectionDifferentialItemData>  $items
     * @param  list<string>  $blockers
     */
    public function __construct(
        public CorrectionRequest $request,
        public ApplicationReviewPublicationResult $originalPublicationResult,
        public CorrectionSubmissionReceipt $submissionReceipt,
        public AdministrativeProcess $process,
        public Application $application,
        public array $items,
        public array $blockers,
        public string $sourceFingerprint,
    ) {}

    /** @return list<CorrectionDifferentialItemData> */
    public function carriedForwardItems(): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (CorrectionDifferentialItemData $item): bool => ! $item->isReviewable(),
        ));
    }

    /** @return list<CorrectionDifferentialItemData> */
    public function reviewableItems(): array
    {
        return array_values(array_filter(
            $this->items,
            static fn (CorrectionDifferentialItemData $item): bool => $item->isReviewable(),
        ));
    }

    public function isStale(): bool
    {
        return $this->blockers !== []
            || collect($this->items)->contains(
                static fn (CorrectionDifferentialItemData $item): bool => $item->stale,
            );
    }
}
