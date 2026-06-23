<?php

namespace App\Services\Applications;

use App\Enums\DocumentStatus;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Services\Documents\DocumentChecklistService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ApplicationDocumentService
{
    public function __construct(
        private readonly DocumentChecklistService $documentChecklistService,
    ) {}

    /**
     * @return array<string|int, mixed>
     */
    public function checklist(Application $application): array
    {
        return $this->documentChecklistService->forApplication($application);
    }

    /**
     * @return Collection<int, ApplicationDocument>
     */
    public function associate(Application $application): Collection
    {
        $checklist = $this->checklist($application);
        $rawItems = $checklist['items'] ?? [];
        /** @var Collection<int, array<string, mixed>> $items */
        $items = $rawItems instanceof Collection
            ? $rawItems
            : new Collection(is_array($rawItems) ? $rawItems : []);

        $invalid = $items
            ->where('is_required', true)
            ->filter(fn (array $item) => ! in_array($item['status'], [
                DocumentStatus::Submitted,
                DocumentStatus::UnderReview,
                DocumentStatus::Validated,
            ], true));

        if ($invalid->isNotEmpty()) {
            throw ValidationException::withMessages([
                'documents' => 'Existem documentos obrigatórios que impedem a submissão.',
            ]);
        }

        $application->applicationDocuments()->delete();

        return $items
            ->filter(fn (array $item) => $item['submission'] !== null)
            ->map(function (array $item) use ($application) {
                return $application->applicationDocuments()->create([
                    'document_submission_id' => $item['submission']->id,
                    'document_type_id' => $item['document_type_id'],
                    'is_required' => $item['is_required'],
                    'status_at_submission' => $item['status']->value,
                ]);
            });
    }

    /**
     * @return Collection<int, ApplicationDocument>
     */
    public function list(Application $application): Collection
    {
        return $application->applicationDocuments()
            ->with(['documentSubmission.currentVersion', 'documentType'])
            ->get();
    }
}
