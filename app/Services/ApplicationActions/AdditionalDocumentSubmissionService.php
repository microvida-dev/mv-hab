<?php

namespace App\Services\ApplicationActions;

use App\Enums\AdditionalDocumentStatus;
use App\Enums\ProcessActionStatus;
use App\Enums\TimelineEventType;
use App\Enums\TimelineEventVisibility;
use App\Models\AdditionalDocumentRequest;
use App\Models\AdditionalDocumentSubmission;
use App\Models\Application;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\ProcessTracking\ProcessTimelineService;
use App\Support\AuditEvents;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdditionalDocumentSubmissionService
{
    public function __construct(
        private readonly ProcessTimelineService $timeline,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(Application $application, User $candidate, array $data, UploadedFile $file): AdditionalDocumentSubmission
    {
        return DB::transaction(function () use ($application, $candidate, $data, $file): AdditionalDocumentSubmission {
            $path = $file->store('additional-documents/'.$application->public_id, 'local');

            $submission = new AdditionalDocumentSubmission([
                'status' => AdditionalDocumentStatus::Submitted,
                'title' => (string) $data['title'],
                'description' => $data['description'] ?? null,
            ]);
            $submission->forceFill([
                'additional_document_request_id' => $data['additional_document_request_id'] ?? null,
                'application_id' => $application->id,
                'user_id' => $candidate->id,
                'file_disk' => 'local',
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'submitted_at' => now(),
            ])->save();

            if ($submission->additional_document_request_id !== null) {
                AdditionalDocumentRequest::query()
                    ->whereKey($submission->additional_document_request_id)
                    ->update([
                        'status' => ProcessActionStatus::PendingReview->value,
                        'fulfilled_at' => now(),
                    ]);
            }

            $this->timeline->record(
                application: $application,
                type: TimelineEventType::AdditionalDocumentSubmitted,
                visibility: TimelineEventVisibility::CandidateVisible,
                title: 'Documento adicional submetido',
                description: $submission->title,
                actor: $candidate,
                related: $submission,
            );

            $this->auditLogger->record(AuditEvents::CREATE, $submission, 'documents', 'additional_document_submit', 'Documento adicional submetido.');

            return $submission->refresh();
        });
    }

    public function decide(AdditionalDocumentSubmission $submission, User $actor, bool $accepted, ?string $reason = null): AdditionalDocumentSubmission
    {
        $application = $submission->application;
        if ($application === null) {
            throw ValidationException::withMessages([
                'application' => 'A submissão documental adicional não tem candidatura associada.',
            ]);
        }

        $submission->forceFill([
            'status' => $accepted ? AdditionalDocumentStatus::Accepted : AdditionalDocumentStatus::Rejected,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'rejection_reason' => $accepted ? null : $reason,
        ])->save();

        $this->timeline->record(
            application: $application,
            type: $accepted ? TimelineEventType::DocumentValidated : TimelineEventType::DocumentRejected,
            visibility: TimelineEventVisibility::CandidateVisible,
            title: $accepted ? 'Documento adicional aceite' : 'Documento adicional rejeitado',
            description: $accepted ? $submission->title : $reason,
            actor: $actor,
            related: $submission,
        );

        return $submission->refresh();
    }

    public function downloadPath(AdditionalDocumentSubmission $submission): string
    {
        return Storage::disk($submission->file_disk)->path((string) $submission->file_path);
    }
}
