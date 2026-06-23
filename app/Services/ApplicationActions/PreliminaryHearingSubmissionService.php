<?php

namespace App\Services\ApplicationActions;

use App\Enums\TimelineEventType;
use App\Enums\TimelineEventVisibility;
use App\Models\Application;
use App\Models\Hearing;
use App\Models\HearingSubmission;
use App\Models\User;
use App\Services\Hearings\HearingSubmissionService as ExistingHearingSubmissionService;
use App\Services\ProcessTracking\ProcessTimelineService;
use Illuminate\Validation\ValidationException;

class PreliminaryHearingSubmissionService
{
    public function __construct(
        private readonly ExistingHearingSubmissionService $hearings,
        private readonly ProcessTimelineService $timeline,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(Hearing $hearing, array $data, User $candidate): HearingSubmission
    {
        $submission = $this->hearings->submit($hearing, [
            'submission_text' => $data['body'] ?? $data['submission_text'] ?? '',
            'document_submission_id' => $data['document_submission_id'] ?? null,
        ], $candidate);

        $application = $submission->application;

        if (! $application instanceof Application) {
            throw ValidationException::withMessages(['application' => 'Pronúncia sem candidatura associada.']);
        }

        $this->timeline->record(
            application: $application,
            type: TimelineEventType::PreliminaryHearingSubmitted,
            visibility: TimelineEventVisibility::CandidateVisible,
            title: 'Pronúncia de audiência submetida',
            description: $hearing->subject,
            actor: $candidate,
            related: $submission,
        );

        return $submission;
    }
}
