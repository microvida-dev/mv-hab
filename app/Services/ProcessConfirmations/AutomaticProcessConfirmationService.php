<?php

namespace App\Services\ProcessConfirmations;

use App\Enums\OfficialNotificationType;
use App\Enums\TimelineEventType;
use App\Enums\TimelineEventVisibility;
use App\Models\Application;
use App\Models\ProcessConfirmation;
use App\Models\User;
use App\Services\Notifications\OfficialNotificationService;
use App\Services\ProcessTracking\ProcessTimelineService;
use Throwable;

class AutomaticProcessConfirmationService
{
    public function __construct(
        private readonly ProcessConfirmationService $confirmations,
        private readonly OfficialNotificationService $notifications,
        private readonly ProcessTimelineService $timeline,
    ) {}

    public function generate(Application $application, ?User $actor = null, bool $forceRegenerate = false): ProcessConfirmation
    {
        $confirmation = $this->confirmations->generate($application, $actor, $forceRegenerate);

        try {
            $this->notifications->createInternal(
                user: $application->user,
                type: OfficialNotificationType::Other,
                subject: 'Número de processo gerado',
                body: $confirmation->message,
                notifiable: $confirmation,
                application: $application,
                actor: $actor,
                actionUrl: route('candidate.applications.show', $application, false),
            );
            $this->confirmations->markSent($confirmation, $actor);
        } catch (Throwable $exception) {
            $this->confirmations->markFailed($confirmation, $exception->getMessage());
        }

        try {
            $this->timeline->record(
                application: $application,
                type: TimelineEventType::SystemEvent,
                visibility: TimelineEventVisibility::CandidateVisible,
                title: 'Número de processo gerado',
                description: $confirmation->process_number,
                actor: $actor,
                related: $confirmation,
                metadata: ['process_number' => $confirmation->process_number],
            );
        } catch (Throwable) {
            // A confirmação é o registo principal; falhas de timeline ficam para auditoria operacional.
        }

        return $confirmation->refresh();
    }
}
