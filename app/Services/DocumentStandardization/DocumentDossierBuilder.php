<?php

namespace App\Services\DocumentStandardization;

use App\Enums\DocumentDossierItemStatus;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\DocumentSubmission;
use App\Models\DocumentType;
use App\Models\RequiredDocument;
use App\Services\Applications\HousingPreferenceSnapshotService;
use App\Services\Documents\DocumentChecklistService;
use App\Services\Documents\DocumentSubmissionContextResolver;
use Illuminate\Support\Collection;

final class DocumentDossierBuilder
{
    public function __construct(
        private readonly DocumentChecklistService $checklist,
        private readonly DocumentSubmissionContextResolver $context,
        private readonly DocumentStandardizationService $standardization,
        private readonly HousingPreferenceSnapshotService $housingPreferences,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array{
     *     items:list<array<string,mixed>>,
     *     summary:array<string,int>,
     *     housing_preferences:list<array<string,mixed>>
     * }
     */
    public function build(
        Application $application,
        array $options = [],
    ): array {
        $application->loadMissing([
            'adhesionRegistration',

            'applicationDocuments.documentSubmission.documentType',
            'applicationDocuments.documentSubmission.requiredDocument.documentType',
            'applicationDocuments.documentSubmission.adhesionRegistration',
            'applicationDocuments.documentSubmission.household',
            'applicationDocuments.documentSubmission.householdMember',
            'applicationDocuments.documentSubmission.incomeRecord.incomeSource',
            'applicationDocuments.documentSubmission.currentHousingSituation',
            'applicationDocuments.documentSubmission.application',
            'applicationDocuments.documentSubmission.contract',

            'documentSubmissions.documentType',
            'documentSubmissions.requiredDocument.documentType',
            'documentSubmissions.adhesionRegistration',
            'documentSubmissions.household',
            'documentSubmissions.householdMember',
            'documentSubmissions.incomeRecord.incomeSource',
            'documentSubmissions.currentHousingSituation',
            'documentSubmissions.application',
            'documentSubmissions.contract',
        ]);

        $checklist = $this->checklist
            ->forApplication($application);

        $rawItems = $checklist['items'] ?? [];

        $rawCollection = match (true) {
            $rawItems instanceof Collection => $rawItems,
            is_array($rawItems) => collect($rawItems),
            default => collect(),
        };

        /** @var Collection<int, array<string, mixed>> $checklistItems */
        $checklistItems = $rawCollection
            ->filter(
                fn (mixed $item): bool => is_array($item),
            )
            ->values();

        /** @var Collection<int, DocumentSubmission> $formalSubmissions */
        $formalSubmissions = $application
            ->applicationDocuments
            ->map(
                fn (ApplicationDocument $link): ?DocumentSubmission => $link->documentSubmission,
            )
            ->filter(
                fn (mixed $submission): bool => $submission instanceof DocumentSubmission,
            )
            ->values();

        $hasFormalDocumentSet = $formalSubmissions->isNotEmpty();

        /** @var Collection<string, DocumentSubmission> $formalByIdentity */
        $formalByIdentity = $formalSubmissions->keyBy(
            fn (DocumentSubmission $submission): string => $this->submissionIdentity($submission),
        );

        /** @var Collection<int, DocumentSubmission> $formalById */
        $formalById = $formalSubmissions->keyBy(
            fn (DocumentSubmission $submission): int => (int) $submission->getKey(),
        );

        $items = [];
        $usedSubmissionIds = [];
        $sort = 1;

        foreach ($checklistItems as $checklistItem) {
            $submission = $this->submissionForChecklistItem(
                item: $checklistItem,
                hasFormalDocumentSet: $hasFormalDocumentSet,
                formalByIdentity: $formalByIdentity,
                formalById: $formalById,
            );

            if ($submission instanceof DocumentSubmission) {
                $usedSubmissionIds[] = (int) $submission->getKey();
            }

            $items[] = $this->checklistItem(
                item: $checklistItem,
                submission: $submission,
                sort: $sort++,
            );
        }

        /** @var Collection<int, DocumentSubmission> $additionalSubmissions */
        $additionalSubmissions = $hasFormalDocumentSet
            ? $formalSubmissions
            : $application->documentSubmissions;

        foreach ($additionalSubmissions as $submission) {
            if (in_array(
                (int) $submission->getKey(),
                $usedSubmissionIds,
                true,
            )) {
                continue;
            }

            $items[] = $this->additionalItem(
                submission: $submission,
                sort: $sort++,
            );
        }

        return [
            'items' => $items,
            'summary' => $this->summary($items),
            'housing_preferences' => $this->housingPreferences
                ->forApplication($application),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  Collection<string, DocumentSubmission>  $formalByIdentity
     * @param  Collection<int, DocumentSubmission>  $formalById
     */
    private function submissionForChecklistItem(
        array $item,
        bool $hasFormalDocumentSet,
        Collection $formalByIdentity,
        Collection $formalById,
    ): ?DocumentSubmission {
        $currentSubmission = $item['submission'] ?? null;

        $currentSubmission = $currentSubmission
            instanceof DocumentSubmission
                ? $currentSubmission
                : null;

        if (! $hasFormalDocumentSet) {
            return $currentSubmission;
        }

        $formalSubmission = $formalByIdentity->get(
            $this->checklistIdentity($item),
        );

        if ($formalSubmission instanceof DocumentSubmission) {
            return $formalSubmission;
        }

        if ($currentSubmission instanceof DocumentSubmission) {
            $formalSubmission = $formalById->get(
                (int) $currentSubmission->getKey(),
            );

            if ($formalSubmission instanceof DocumentSubmission) {
                return $formalSubmission;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function checklistItem(
        array $item,
        ?DocumentSubmission $submission,
        int $sort,
    ): array {
        $required = $item['required_document'] ?? null;

        $required = $required instanceof RequiredDocument
            ? $required
            : null;

        if (
            ! $required instanceof RequiredDocument
            && $submission instanceof DocumentSubmission
            && $submission->required_document_id !== null
        ) {
            $submissionRequirement = $submission->getRelationValue(
                'requiredDocument',
            );

            $required = $submissionRequirement
                instanceof RequiredDocument
                    ? $submissionRequirement
                    : null;
        }

        $documentType = $item['document_type'] ?? null;
        $documentType = $documentType instanceof DocumentType
            ? $documentType
            : $submission?->documentType;

        $status = $this->standardization
            ->itemStatus($submission);

        $requirementInstance = max(
            1,
            (int) ($item['requirement_instance'] ?? 1),
        );

        $requiredSubmissions = max(
            1,
            (int) ($item['required_submissions'] ?? 1),
        );

        $targetType = $item['target_type'] ?? null;
        $targetType = is_string($targetType)
            ? $targetType
            : null;

        $targetId = $item['target_id'] ?? null;
        $targetId = is_numeric($targetId)
            ? (int) $targetId
            : null;

        $targetLabel = $item['target_label'] ?? null;
        $targetLabel = is_string($targetLabel)
            ? $targetLabel
            : null;

        return [
            'source' => 'checklist',
            'required_document_id' => $required?->id,
            'document_submission_id' => $submission?->id,
            'document_type_id' => $documentType?->id,

            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_label' => $targetLabel,

            'requirement_instance' => $requirementInstance,
            'required_submissions' => $requiredSubmissions,
            'position_label' => $requiredSubmissions > 1
                ? $requirementInstance.'/'.$requiredSubmissions
                : null,

            'reference_period' => $submission?->reference_period?->toDateString(),

            'category' => $this->standardization->category(
                data_get($documentType, 'category.value'),
            ),

            'label' => $required instanceof RequiredDocument
                ? $this->standardization->label($required)
                : (
                    $submission instanceof DocumentSubmission
                        ? $this->standardization->label($submission)
                        : 'Documento'
                ),

            'status' => $status->value,
            'sort_order' => $sort,
            'is_required' => (bool) ($item['is_required'] ?? false),

            'is_missing' => $status === DocumentDossierItemStatus::Missing,

            'is_rejected' => $status === DocumentDossierItemStatus::Rejected,

            'is_expired' => $status === DocumentDossierItemStatus::Expired,

            'is_validated' => $status === DocumentDossierItemStatus::Validated,

            'notes' => $submission?->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function additionalItem(
        DocumentSubmission $submission,
        int $sort,
    ): array {
        $required = $submission->required_document_id !== null
            ? $submission->requiredDocument
            : null;
        $documentType = $submission->documentType;

        $status = $this->standardization
            ->itemStatus($submission);

        $context = $this->context->resolve($submission);

        return [
            'source' => 'associated_submission',
            'required_document_id' => $context['required_document_id'],

            'document_submission_id' => $submission->id,
            'document_type_id' => $submission->document_type_id,

            'target_type' => $context['target_type'],
            'target_id' => $context['target_id'],
            'target_label' => $context['target_label'],

            'requirement_instance' => $context['requirement_instance'],

            'required_submissions' => $context['required_submissions'],

            'position_label' => $context['position_label'],

            'reference_period' => $context['reference_period'],

            'category' => $this->standardization->category(
                data_get($documentType, 'category.value'),
            ),

            'label' => $this->standardization
                ->label($submission),

            'status' => $status->value,
            'sort_order' => $sort,
            'is_required' => $required instanceof RequiredDocument
                && (bool) $required->is_required,

            'is_missing' => $status === DocumentDossierItemStatus::Missing,

            'is_rejected' => $status === DocumentDossierItemStatus::Rejected,

            'is_expired' => $status === DocumentDossierItemStatus::Expired,

            'is_validated' => $status === DocumentDossierItemStatus::Validated,

            'notes' => $submission->notes,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, int>
     */
    private function summary(array $items): array
    {
        return [
            'missing' => count(array_filter(
                $items,
                static fn (array $item): bool => (bool) $item['is_missing'],
            )),

            'rejected' => count(array_filter(
                $items,
                static fn (array $item): bool => (bool) $item['is_rejected'],
            )),

            'expired' => count(array_filter(
                $items,
                static fn (array $item): bool => (bool) $item['is_expired'],
            )),

            'validated' => count(array_filter(
                $items,
                static fn (array $item): bool => (bool) $item['is_validated'],
            )),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function checklistIdentity(array $item): string
    {
        return $this->identity(
            requiredDocumentId: $this->nullableInteger(
                $item['required_document_id'] ?? null,
            ),
            targetType: is_string($item['target_type'] ?? null)
                ? $item['target_type']
                : null,
            targetId: $this->nullableInteger(
                $item['target_id'] ?? null,
            ),
            requirementInstance: max(
                1,
                (int) ($item['requirement_instance'] ?? 1),
            ),
        );
    }

    private function submissionIdentity(
        DocumentSubmission $submission,
    ): string {
        return $this->context->identity($submission);
    }

    private function identity(
        ?int $requiredDocumentId,
        ?string $targetType,
        ?int $targetId,
        int $requirementInstance,
    ): string {
        return implode('|', [
            $requiredDocumentId ?? '',
            $targetType ?? '',
            $targetId ?? '',
            $requirementInstance,
        ]);
    }

    private function nullableInteger(mixed $value): ?int
    {
        return is_numeric($value)
            ? (int) $value
            : null;
    }
}
