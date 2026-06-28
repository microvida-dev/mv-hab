<?php

namespace App\Data\Cases;

class CaseWorkspaceData
{
    /**
     * @param  array<string, mixed>  $supportedTypes
     * @param  list<array<string, mixed>>  $tabs
     * @param  list<CaseTimelineItemData>  $timeline
     * @param  list<CaseChecklistItemData>  $checklist
     * @param  list<CaseRelationData>  $relations
     * @param  list<CaseDocumentData>  $documents
     * @param  list<CaseTaskData>  $tasks
     * @param  list<CaseCommunicationData>  $communications
     * @param  list<CaseTimelineItemData>  $history
     * @param  list<CaseSearchResultData>  $searchResults
     * @param  array<string, mixed>  $sidebar
     */
    public function __construct(
        public readonly string $caseType,
        public readonly array $supportedTypes,
        public readonly CaseHeaderData $header,
        public readonly CaseSummaryData $summary,
        public readonly array $tabs,
        public readonly array $timeline,
        public readonly array $checklist,
        public readonly array $relations,
        public readonly array $documents,
        public readonly array $tasks,
        public readonly array $communications,
        public readonly array $history,
        public readonly CaseActionData $nextAction,
        public readonly array $sidebar,
        public readonly array $searchResults,
        public readonly ?string $searchQuery,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'case_type' => $this->caseType,
            'supported_types' => $this->supportedTypes,
            'summary' => $this->header->toArray(),
            'summary_items' => $this->summary->toArray(),
            'tabs' => $this->tabs,
            'timeline' => array_map(fn (CaseTimelineItemData $item): array => $item->toArray(), $this->timeline),
            'checklist' => array_map(fn (CaseChecklistItemData $item): array => $item->toArray(), $this->checklist),
            'relations' => array_map(fn (CaseRelationData $item): array => $item->toArray(), $this->relations),
            'documents' => array_map(fn (CaseDocumentData $item): array => $item->toArray(), $this->documents),
            'tasks' => array_map(fn (CaseTaskData $item): array => $item->toArray(), $this->tasks),
            'communications' => array_map(fn (CaseCommunicationData $item): array => $item->toArray(), $this->communications),
            'history' => array_map(fn (CaseTimelineItemData $item): array => $item->toArray(), $this->history),
            'next_action' => $this->nextAction->toArray(),
            'sidebar' => $this->sidebar,
            'search_results' => array_map(fn (CaseSearchResultData $item): array => $item->toArray(), $this->searchResults),
            'contextual_search_query' => $this->searchQuery,
        ];
    }
}
