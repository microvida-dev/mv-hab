<?php

namespace App\Services\ApplicationActions;

use App\Enums\OfficialNotificationType;
use App\Enums\ProcessActionStatus;
use App\Enums\TimelineEventType;
use App\Enums\TimelineEventVisibility;
use App\Models\AdditionalDocumentRequest;
use App\Models\Application;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\OfficialNotificationService;
use App\Services\ProcessTracking\ProcessTimelineService;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdditionalDocumentRequestService
{
    public function __construct(
        private readonly ProcessTimelineService $timeline,
        private readonly OfficialNotificationService $notifications,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Application $application, User $actor, array $data): AdditionalDocumentRequest
    {
        return DB::transaction(function () use ($application, $actor, $data): AdditionalDocumentRequest {
            $request = new AdditionalDocumentRequest([
                'status' => ProcessActionStatus::Available,
                'title' => (string) $data['title'],
                'description' => $data['description'] ?? null,
                'due_at' => $data['due_at'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);
            $request->forceFill([
                'request_number' => $this->number(),
                'application_id' => $application->id,
                'user_id' => $application->user_id,
                'document_type_id' => $data['document_type_id'] ?? null,
                'required_document_id' => $data['required_document_id'] ?? null,
                'issued_by' => $actor->id,
                'issued_at' => now(),
            ])->save();

            $this->timeline->record(
                application: $application,
                type: TimelineEventType::AdditionalDocumentRequested,
                visibility: TimelineEventVisibility::CandidateVisible,
                title: 'Documento adicional solicitado',
                description: $request->title,
                actor: $actor,
                related: $request,
                dueAt: $request->due_at,
            );

            $candidate = $application->user()->first();
            if ($candidate instanceof User) {
                $this->notifications->createInternal(
                    user: $candidate,
                    type: OfficialNotificationType::Other,
                    subject: 'Documento adicional solicitado',
                    body: 'Foi solicitado um documento adicional para o seu processo.',
                    notifiable: $request,
                    application: $application,
                    actor: $actor,
                    actionUrl: route('candidate.additional-documents.create', $application, false),
                );
            }

            $this->auditLogger->record(AuditEvents::CREATE, $request, 'documents', 'additional_document_request_create', 'Pedido de documento adicional criado.');

            return $request->refresh();
        });
    }

    private function number(): string
    {
        do {
            $number = 'DOC-ADD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (AdditionalDocumentRequest::query()->where('request_number', $number)->exists());

        return $number;
    }
}
