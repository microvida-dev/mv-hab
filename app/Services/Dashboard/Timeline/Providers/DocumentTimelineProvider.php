<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\AdditionalDocumentStatus;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Enums\DocumentDossierStatus;
use App\Enums\DocumentStatus;
use App\Enums\ProcessActionStatus;
use App\Models\AdditionalDocumentRequest;
use App\Models\AdditionalDocumentSubmission;
use App\Models\DocumentDossier;
use App\Models\DocumentSubmission;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;

class DocumentTimelineProvider implements TimelineProviderInterface
{
    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('documents.view')) {
            return [];
        }

        return array_merge(
            $this->documentSubmissionEvents(),
            $this->dossierEvents(),
            $this->additionalRequestEvents(),
            $this->additionalSubmissionEvents(),
        );
    }

    /**
     * @return array<int, TimelineEvent>
     */
    private function documentSubmissionEvents(): array
    {
        return DocumentSubmission::query()
            ->with(['application', 'user', 'documentType'])
            ->whereIn('status', [
                DocumentStatus::Submitted->value,
                DocumentStatus::UnderReview->value,
            ])
            ->whereNotNull('submitted_at')
            ->orderBy('submitted_at')
            ->limit(30)
            ->get()
            ->map(fn (DocumentSubmission $document): TimelineEvent => $document->status === DocumentStatus::UnderReview
                ? $this->documentUnderReviewEvent($document)
                : $this->documentSubmittedEvent($document)
            )
            ->all();
    }

    /**
     * @return array<int, TimelineEvent>
     */
    private function dossierEvents(): array
    {
        return DocumentDossier::query()
            ->with(['application', 'user'])
            ->whereIn('status', [
                DocumentDossierStatus::Incomplete->value,
                DocumentDossierStatus::RequiresReview->value,
            ])
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get()
            ->map(fn (DocumentDossier $dossier): TimelineEvent => $this->dossierIncompleteEvent($dossier))
            ->all();
    }

    /**
     * @return array<int, TimelineEvent>
     */
    private function additionalRequestEvents(): array
    {
        return AdditionalDocumentRequest::query()
            ->with(['application', 'user', 'documentType'])
            ->whereIn('status', [
                ProcessActionStatus::Available->value,
                ProcessActionStatus::PendingReview->value,
                ProcessActionStatus::Expired->value,
            ])
            ->whereNotNull('due_at')
            ->orderBy('due_at')
            ->limit(30)
            ->get()
            ->map(fn (AdditionalDocumentRequest $request): TimelineEvent => $this->additionalRequestedEvent($request))
            ->all();
    }

    /**
     * @return array<int, TimelineEvent>
     */
    private function additionalSubmissionEvents(): array
    {
        return AdditionalDocumentSubmission::query()
            ->with(['application', 'user', 'additionalDocumentRequest'])
            ->whereIn('status', [
                AdditionalDocumentStatus::Submitted->value,
                AdditionalDocumentStatus::UnderReview->value,
            ])
            ->whereNotNull('submitted_at')
            ->orderBy('submitted_at')
            ->limit(30)
            ->get()
            ->map(fn (AdditionalDocumentSubmission $submission): TimelineEvent => $this->additionalSubmittedEvent($submission))
            ->all();
    }

    private function documentSubmittedEvent(DocumentSubmission $document): TimelineEvent
    {
        return new TimelineEvent(
            id: 'document-submitted-'.$document->getKey(),
            type: TimelineType::DocumentSubmitted,
            title: 'Documento submetido',
            description: $this->documentDescription($document),
            route: route('backoffice.cases.documents.show', $document),
            datetime: $document->submitted_at,
            priority: TimelinePriority::Medium,
            icon: 'document',
            tone: 'info',
            workspace: TimelineWorkspace::Applications,
            metadata: $this->documentMetadata($document),
        );
    }

    private function documentUnderReviewEvent(DocumentSubmission $document): TimelineEvent
    {
        return new TimelineEvent(
            id: 'document-under-review-'.$document->getKey(),
            type: TimelineType::DocumentUnderReview,
            title: 'Documento em análise',
            description: $this->documentDescription($document),
            route: route('backoffice.cases.documents.show', $document),
            datetime: $document->reviewed_at ?? $document->submitted_at,
            priority: TimelinePriority::High,
            icon: 'document-search',
            tone: 'warning',
            workspace: TimelineWorkspace::Applications,
            metadata: $this->documentMetadata($document),
        );
    }

    private function dossierIncompleteEvent(DocumentDossier $dossier): TimelineEvent
    {
        return new TimelineEvent(
            id: 'document-dossier-incomplete-'.$dossier->getKey(),
            type: TimelineType::DocumentDossierIncomplete,
            title: 'Dossier documental incompleto',
            description: trim(($dossier->dossier_number ?? 'Dossier').' · '.$dossier->user?->name),
            route: route('backoffice.applications.index'),
            datetime: $dossier->updated_at,
            priority: TimelinePriority::High,
            icon: 'folder-warning',
            tone: 'warning',
            workspace: TimelineWorkspace::Applications,
            metadata: [
                'document_dossier_id' => $dossier->getKey(),
                'dossier_number' => $dossier->dossier_number,
                'application_id' => $dossier->application_id,
                'user_id' => $dossier->user_id,
                'status' => $dossier->status?->value ?? $dossier->status,
                'missing_documents_count' => $dossier->missing_documents_count,
                'rejected_documents_count' => $dossier->rejected_documents_count,
                'expired_documents_count' => $dossier->expired_documents_count,
            ],
        );
    }

    private function additionalRequestedEvent(AdditionalDocumentRequest $request): TimelineEvent
    {
        return new TimelineEvent(
            id: 'additional-document-requested-'.$request->getKey(),
            type: TimelineType::AdditionalDocumentRequested,
            title: 'Pedido de documentação adicional',
            description: trim(($request->request_number ?? 'Pedido').' · '.$request->user?->name),
            route: route('backoffice.additional-document-requests.index'),
            datetime: $request->due_at,
            priority: $request->due_at?->isPast() ? TimelinePriority::Critical : TimelinePriority::High,
            icon: 'document-add',
            tone: $request->due_at?->isPast() ? 'danger' : 'warning',
            workspace: TimelineWorkspace::Applications,
            metadata: [
                'additional_document_request_id' => $request->getKey(),
                'request_number' => $request->request_number,
                'application_id' => $request->application_id,
                'user_id' => $request->user_id,
                'status' => $request->status?->value ?? $request->status,
                'due_at' => $request->due_at?->toIso8601String(),
            ],
        );
    }

    private function additionalSubmittedEvent(AdditionalDocumentSubmission $submission): TimelineEvent
    {
        return new TimelineEvent(
            id: 'additional-document-submitted-'.$submission->getKey(),
            type: TimelineType::AdditionalDocumentSubmitted,
            title: 'Documentação adicional recebida',
            description: trim(($submission->title ?? 'Submissão adicional').' · '.$submission->user?->name),
            route: route('backoffice.additional-document-submissions.show', $submission),
            datetime: $submission->submitted_at,
            priority: TimelinePriority::Medium,
            icon: 'upload',
            tone: 'info',
            workspace: TimelineWorkspace::Applications,
            metadata: [
                'additional_document_submission_id' => $submission->getKey(),
                'additional_document_request_id' => $submission->additional_document_request_id,
                'application_id' => $submission->application_id,
                'user_id' => $submission->user_id,
                'status' => $submission->status?->value ?? $submission->status,
                'submitted_at' => $submission->submitted_at?->toIso8601String(),
            ],
        );
    }

    private function documentDescription(DocumentSubmission $document): string
    {
        $type = $document->documentType?->name ?? $document->title ?? 'Documento';
        $user = $document->user?->name ?? 'Candidato';

        return trim("{$type} · {$user}");
    }

    /**
     * @return array<string, mixed>
     */
    private function documentMetadata(DocumentSubmission $document): array
    {
        return [
            'document_submission_id' => $document->getKey(),
            'application_id' => $document->application_id,
            'user_id' => $document->user_id,
            'document_type_id' => $document->document_type_id,
            'document_type_name' => $document->documentType?->name,
            'status' => $document->status?->value ?? $document->status,
            'submitted_at' => $document->submitted_at?->toIso8601String(),
        ];
    }
}
