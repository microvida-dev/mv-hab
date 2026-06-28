<?php

namespace App\Services\Cases;

use App\Data\Cases\CaseChecklistItemData;
use App\Data\Cases\CaseCommunicationData;
use App\Data\Cases\CaseDocumentData;
use App\Data\Cases\CaseRelationData;
use App\Data\Cases\CaseSearchResultData;
use App\Data\Cases\CaseTaskData;
use App\Data\Cases\CaseTimelineItemData;

class CaseSearchService
{
    /**
     * @param  list<CaseTimelineItemData>  $timeline
     * @param  list<CaseChecklistItemData>  $checklist
     * @param  list<CaseRelationData>  $relations
     * @param  list<CaseDocumentData>  $documents
     * @param  list<CaseTaskData>  $tasks
     * @param  list<CaseCommunicationData>  $communications
     * @return list<CaseSearchResultData>
     */
    public function search(?string $query, array $timeline, array $checklist, array $relations, array $documents, array $tasks, array $communications): array
    {
        $term = trim((string) $query);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $results = array_merge(
            $this->timeline($term, $timeline),
            $this->checklist($term, $checklist),
            $this->relations($term, $relations),
            $this->documents($term, $documents),
            $this->tasks($term, $tasks),
            $this->communications($term, $communications),
        );

        return array_slice($results, 0, 12);
    }

    /**
     * @param  list<CaseTimelineItemData>  $items
     * @return list<CaseSearchResultData>
     */
    private function timeline(string $term, array $items): array
    {
        return $this->filter($term, $items, 'Cronologia', 'case-tab-timeline');
    }

    /**
     * @param  list<CaseChecklistItemData>  $items
     * @return list<CaseSearchResultData>
     */
    private function checklist(string $term, array $items): array
    {
        return $this->filter($term, $items, 'Checklist', 'case-tab-checklist');
    }

    /**
     * @param  list<CaseRelationData>  $items
     * @return list<CaseSearchResultData>
     */
    private function relations(string $term, array $items): array
    {
        return $this->filter($term, $items, 'Relações', 'case-tab-relations');
    }

    /**
     * @param  list<CaseDocumentData>  $items
     * @return list<CaseSearchResultData>
     */
    private function documents(string $term, array $items): array
    {
        return $this->filter($term, $items, 'Documentos', 'case-tab-documents');
    }

    /**
     * @param  list<CaseTaskData>  $items
     * @return list<CaseSearchResultData>
     */
    private function tasks(string $term, array $items): array
    {
        return $this->filter($term, $items, 'Tarefas', 'case-tab-tasks');
    }

    /**
     * @param  list<CaseCommunicationData>  $items
     * @return list<CaseSearchResultData>
     */
    private function communications(string $term, array $items): array
    {
        return $this->filter($term, $items, 'Comunicações', 'case-tab-communications');
    }

    /**
     * @param  list<CaseTimelineItemData|CaseChecklistItemData|CaseRelationData|CaseDocumentData|CaseTaskData|CaseCommunicationData>  $items
     * @return list<CaseSearchResultData>
     */
    private function filter(string $term, array $items, string $section, string $anchor): array
    {
        $results = [];

        foreach ($items as $item) {
            $haystack = $this->searchableText($item);
            if (! str_contains(str($haystack)->lower()->toString(), str($term)->lower()->toString())) {
                continue;
            }

            $results[] = new CaseSearchResultData($this->resultLabel($item), $this->resultDescription($item, $section), $section, '#'.$anchor);
        }

        return $results;
    }

    private function searchableText(CaseTimelineItemData|CaseChecklistItemData|CaseRelationData|CaseDocumentData|CaseTaskData|CaseCommunicationData $item): string
    {
        return match (true) {
            $item instanceof CaseTimelineItemData => $item->title.' '.$item->description,
            $item instanceof CaseChecklistItemData => $item->label.' '.$item->description,
            $item instanceof CaseRelationData => $item->label.' '.$item->description,
            $item instanceof CaseDocumentData => $item->label.' '.$item->description,
            $item instanceof CaseTaskData => $item->label.' '.$item->status.' '.$item->priority,
            $item instanceof CaseCommunicationData => $item->label.' '.$item->description,
        };
    }

    private function resultLabel(CaseTimelineItemData|CaseChecklistItemData|CaseRelationData|CaseDocumentData|CaseTaskData|CaseCommunicationData $item): string
    {
        return match (true) {
            $item instanceof CaseTimelineItemData => $item->title,
            $item instanceof CaseChecklistItemData => $item->label,
            $item instanceof CaseRelationData => $item->label,
            $item instanceof CaseDocumentData => $item->label,
            $item instanceof CaseTaskData => $item->label,
            $item instanceof CaseCommunicationData => $item->label,
        };
    }

    private function resultDescription(CaseTimelineItemData|CaseChecklistItemData|CaseRelationData|CaseDocumentData|CaseTaskData|CaseCommunicationData $item, string $fallback): string
    {
        return match (true) {
            $item instanceof CaseTimelineItemData => $item->description ?? $fallback,
            $item instanceof CaseChecklistItemData => $item->description,
            $item instanceof CaseRelationData => $item->description,
            $item instanceof CaseDocumentData => $item->description,
            $item instanceof CaseTaskData => $item->status.' · '.$item->priority,
            $item instanceof CaseCommunicationData => $item->description,
        };
    }
}
