<?php

namespace App\Services\Lists;

use App\Enums\ListEntryStatus;
use App\Enums\ListEntryType;
use App\Enums\RankingEntryStatus;
use App\Models\Application;
use App\Models\ProvisionalList;
use App\Models\ProvisionalListEntry;
use App\Models\RankingEntry;
use RuntimeException;

class ListEntryBuilderService
{
    public function __construct(private readonly ListAnonymizationService $anonymizationService) {}

    public function createFromRankingEntry(ProvisionalList $list, RankingEntry $rankingEntry): ProvisionalListEntry
    {
        $rankingEntry->loadMissing(['application.user', 'applicationScore']);
        $application = $rankingEntry->application;
        $score = $rankingEntry->applicationScore;

        if (! $application instanceof Application) {
            throw new RuntimeException('Entrada de ranking sem candidatura associada.');
        }

        $candidate = $application->user()->first();

        [$entryType, $status] = $this->mapStatus($rankingEntry->status);

        $entry = new ProvisionalListEntry;
        $entry->forceFill([
            'provisional_list_id' => $list->id,
            'application_id' => $application->id,
            'application_score_id' => $rankingEntry->application_score_id,
            'ranking_entry_id' => $rankingEntry->id,
            'user_id' => $application->user_id,
            'entry_type' => $entryType,
            'status' => $status,
            'rank_position' => $rankingEntry->rank_position,
            'total_score' => $rankingEntry->total_score,
            'public_identifier' => $this->anonymizationService->publicIdentifier($application, $list->id),
            'candidate_name_masked' => $this->anonymizationService->maskedName($candidate?->name, $list->anonymization_mode),
            'application_number_masked' => $this->anonymizationService->maskedApplicationNumber($application->application_number),
            'exclusion_reason' => $score?->exclusion_reason,
            'exclusion_legal_basis' => null,
            'decision_summary' => $status === ListEntryStatus::Excluded ? 'Candidatura excluída do ranking interno.' : 'Candidatura integrada na lista provisória.',
            'metadata' => [
                'ranking_entry_status' => $rankingEntry->status->value,
                'is_tied' => $rankingEntry->is_tied,
            ],
        ])->save();

        return $entry;
    }

    /**
     * @return array{0: ListEntryType, 1: ListEntryStatus}
     */
    private function mapStatus(RankingEntryStatus $status): array
    {
        return match ($status) {
            RankingEntryStatus::Excluded => [ListEntryType::Excluded, ListEntryStatus::Excluded],
            RankingEntryStatus::RequiresManualReview => [ListEntryType::Ranked, ListEntryStatus::PendingReview],
            default => [ListEntryType::Ranked, ListEntryStatus::Ranked],
        };
    }
}
