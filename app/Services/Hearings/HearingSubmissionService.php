<?php

namespace App\Services\Hearings;

use App\Enums\HearingStatus;
use App\Enums\HearingSubmissionStatus;
use App\Enums\OfficialNotificationType;
use App\Models\Application;
use App\Models\DocumentSubmission;
use App\Models\Hearing;
use App\Models\HearingSubmission;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\OfficialNotificationService;
use App\Support\AuditEvents;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HearingSubmissionService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly OfficialNotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(Hearing $hearing, array $data, User $candidate): HearingSubmission
    {
        if ($hearing->user_id !== $candidate->id || $this->hearingStatus($hearing) !== HearingStatus::Open || ! $hearing->candidate_visible) {
            throw ValidationException::withMessages(['hearing' => 'Audiência indisponível para pronúncia.']);
        }

        $deadline = $hearing->getAttribute('deadline_at');

        if ($deadline instanceof CarbonInterface && $deadline->lt(now())) {
            throw ValidationException::withMessages(['hearing' => 'O prazo de audiência terminou.']);
        }

        if (! empty($data['document_submission_id'])) {
            $this->assertDocumentBelongsToCandidate((int) $data['document_submission_id'], $candidate, $hearing);
        }

        $notificationService = $this->notificationService;

        return DB::transaction(function () use ($hearing, $data, $candidate, $notificationService) {
            $submission = new HearingSubmission([
                'submission_text' => $data['submission_text'],
                'document_submission_id' => $data['document_submission_id'] ?? null,
            ]);
            $submission->forceFill([
                'hearing_id' => $hearing->id,
                'application_id' => $hearing->application_id,
                'user_id' => $candidate->id,
                'status' => HearingSubmissionStatus::Submitted,
                'submitted_at' => now(),
            ])->save();

            $hearing->forceFill(['status' => HearingStatus::Submitted, 'submitted_at' => now()])->save();

            $notificationService->createInternal(
                user: $candidate,
                type: OfficialNotificationType::HearingSubmissionReceived,
                subject: 'Pronúncia de audiência recebida',
                body: 'A sua pronúncia foi submetida e ficará disponível para análise pelos serviços municipais.',
                notifiable: $submission,
                application: $this->optionalApplication($hearing),
                actor: $candidate,
            );

            $this->auditLogger->record(AuditEvents::CREATE, $submission, 'complaints', 'hearing_submission_submit', 'Pronúncia de audiência submetida.');

            return $submission->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function review(HearingSubmission $submission, array $data, User $actor): HearingSubmission
    {
        $submission->forceFill([
            'status' => $data['accepted'] ? HearingSubmissionStatus::Accepted : HearingSubmissionStatus::Rejected,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'review_result' => $data['review_result'] ?? ($data['accepted'] ? 'accepted' : 'rejected'),
            'review_notes' => $data['review_notes'] ?? null,
        ])->save();
        $this->requiredHearing($submission)->forceFill([
            'status' => HearingStatus::Completed,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'closed_at' => now(),
        ])->save();

        $this->auditLogger->record(AuditEvents::DECISION, $submission, 'complaints', 'hearing_submission_review', 'Pronúncia de audiência analisada.');

        return $submission->refresh();
    }

    private function assertDocumentBelongsToCandidate(int $documentId, User $candidate, Hearing $hearing): void
    {
        $owned = DocumentSubmission::query()
            ->whereKey($documentId)
            ->where('user_id', $candidate->id)
            ->where(fn ($query) => $query->whereNull('application_id')->orWhere('application_id', $hearing->application_id))
            ->exists();

        if (! $owned) {
            throw ValidationException::withMessages(['document_submission_id' => 'Documento associado inválido.']);
        }
    }

    private function hearingStatus(Hearing $hearing): ?HearingStatus
    {
        $status = $hearing->getAttribute('status');

        if ($status instanceof HearingStatus) {
            return $status;
        }

        return is_string($status) ? HearingStatus::tryFrom($status) : null;
    }

    private function optionalApplication(Hearing $hearing): ?Application
    {
        $application = $hearing->application;

        return $application instanceof Application ? $application : null;
    }

    private function requiredHearing(HearingSubmission $submission): Hearing
    {
        $hearing = $submission->hearing;

        if (! $hearing instanceof Hearing) {
            throw ValidationException::withMessages(['hearing' => 'A pronúncia não tem audiência associada.']);
        }

        return $hearing;
    }
}
