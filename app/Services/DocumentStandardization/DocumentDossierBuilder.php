<?php

namespace App\Services\DocumentStandardization;

use App\Enums\DocumentDossierItemStatus;
use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\RequiredDocument;

class DocumentDossierBuilder
{
    public function __construct(private readonly DocumentStandardizationService $standardization) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array{items:list<array<string,mixed>>,summary:array<string,int>}
     */
    public function build(Application $application, array $options = []): array
    {
        $application->loadMissing(['contest', 'program', 'documentSubmissions.documentType']);

        $required = RequiredDocument::query()
            ->with('documentType')
            ->where('is_active', true)
            ->where(function ($query) use ($application): void {
                $query
                    ->where('contest_id', $application->contest_id)
                    ->orWhere('program_id', $application->program_id)
                    ->orWhere(function ($builder): void {
                        $builder->whereNull('contest_id')->whereNull('program_id');
                    });
            })
            ->orderBy('sort_order')
            ->get();

        $submissions = $application->documentSubmissions;
        $items = [];
        $sort = 1;

        foreach ($required as $requiredDocument) {
            $submission = $submissions->first(fn (DocumentSubmission $document): bool => $document->required_document_id === $requiredDocument->id
                || $document->document_type_id === $requiredDocument->document_type_id);
            $status = $this->standardization->itemStatus($submission);
            $items[] = $this->item($status, $sort++, $requiredDocument, $submission);
        }

        foreach ($submissions->whereNull('required_document_id') as $submission) {
            if ($required->contains('document_type_id', $submission->document_type_id)) {
                continue;
            }

            $items[] = $this->item($this->standardization->itemStatus($submission), $sort++, null, $submission);
        }

        return [
            'items' => $items,
            'summary' => [
                'missing' => count(array_filter($items, static fn (array $item): bool => (bool) $item['is_missing'])),
                'rejected' => count(array_filter($items, static fn (array $item): bool => (bool) $item['is_rejected'])),
                'expired' => count(array_filter($items, static fn (array $item): bool => (bool) $item['is_expired'])),
                'validated' => count(array_filter($items, static fn (array $item): bool => (bool) $item['is_validated'])),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(DocumentDossierItemStatus $status, int $sort, ?RequiredDocument $required, ?DocumentSubmission $submission): array
    {
        $documentType = $required instanceof RequiredDocument ? $required->documentType : $submission?->documentType;
        $category = $this->standardization->category(data_get($documentType, 'category.value'));
        $label = match (true) {
            $required instanceof RequiredDocument => $this->standardization->label($required),
            $submission instanceof DocumentSubmission => $this->standardization->label($submission),
            default => 'Documento',
        };

        return [
            'required_document_id' => $required?->id,
            'document_submission_id' => $submission?->id,
            'document_type_id' => $documentType?->id,
            'category' => $category,
            'label' => $label,
            'status' => $status->value,
            'sort_order' => $sort,
            'is_required' => $required instanceof RequiredDocument && (bool) $required->is_required,
            'is_missing' => $status === DocumentDossierItemStatus::Missing,
            'is_rejected' => $status === DocumentDossierItemStatus::Rejected,
            'is_expired' => $status === DocumentDossierItemStatus::Expired,
            'is_validated' => $status === DocumentDossierItemStatus::Validated,
            'notes' => $submission?->notes,
        ];
    }
}
