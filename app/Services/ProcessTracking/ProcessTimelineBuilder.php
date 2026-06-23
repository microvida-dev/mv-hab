<?php

namespace App\Services\ProcessTracking;

use App\Enums\TimelineEventVisibility;
use App\Models\AdministrativeProcess;
use App\Models\Application;
use Illuminate\Support\Collection;

class ProcessTimelineBuilder
{
    /**
     * @return Collection<int, array{date: mixed, type: string, title: string, description: string|null, visibility: string, due_at: mixed, public_status: string|null}>
     */
    public function forCandidate(AdministrativeProcess|Application $subject): Collection
    {
        return $this->build($this->applicationFrom($subject), false);
    }

    /**
     * @return Collection<int, array{date: mixed, type: string, title: string, description: string|null, visibility: string, due_at: mixed, public_status: string|null}>
     */
    public function forBackoffice(AdministrativeProcess|Application $subject): Collection
    {
        return $this->build($this->applicationFrom($subject), true);
    }

    private function applicationFrom(AdministrativeProcess|Application $subject): Application
    {
        if ($subject instanceof Application) {
            return $subject;
        }

        return $subject->application()->firstOrFail();
    }

    /**
     * @return Collection<int, array{date: mixed, type: string, title: string, description: string|null, visibility: string, due_at: mixed, public_status: string|null}>
     */
    private function build(Application $application, bool $includeInternal): Collection
    {
        $application->loadMissing([
            'statusHistories',
            'processTimelineEvents',
            'correctionRequests.responses',
            'hearings.submissions',
            'officialNotifications',
            'documentSubmissions',
            'housingVisits',
            'supportTickets',
            'simulationInconsistencies',
        ]);

        $events = collect();

        foreach ($application->processTimelineEvents as $event) {
            if (! $includeInternal && $event->visibility !== TimelineEventVisibility::CandidateVisible) {
                continue;
            }

            $events->push([
                'date' => $event->occurred_at,
                'type' => $event->event_type->value,
                'title' => $event->title,
                'description' => $event->description,
                'visibility' => $event->visibility->value,
                'due_at' => $event->due_at,
                'public_status' => $event->public_status?->value,
            ]);
        }

        if ($application->submitted_at !== null) {
            $events->push([
                'date' => $application->submitted_at,
                'type' => 'application_submitted',
                'title' => 'Candidatura submetida',
                'description' => $application->application_number,
                'visibility' => TimelineEventVisibility::CandidateVisible->value,
                'due_at' => null,
                'public_status' => null,
            ]);
        }

        foreach ($application->statusHistories as $history) {
            $events->push([
                'date' => $history->created_at,
                'type' => 'status_changed',
                'title' => $history->to_status->label(),
                'description' => $includeInternal ? $history->reason : 'Estado atualizado no processo.',
                'visibility' => $includeInternal ? TimelineEventVisibility::BackofficeOnly->value : TimelineEventVisibility::CandidateVisible->value,
                'due_at' => null,
                'public_status' => null,
            ]);
        }

        foreach ($application->correctionRequests as $request) {
            if (! $includeInternal && ! $request->candidate_visible) {
                continue;
            }
            $events->push([
                'date' => $request->issued_at ?? $request->created_at,
                'type' => 'correction_requested',
                'title' => 'Pedido de aperfeiçoamento',
                'description' => $request->subject,
                'visibility' => TimelineEventVisibility::CandidateVisible->value,
                'due_at' => $request->response_deadline_at,
                'public_status' => null,
            ]);
        }

        foreach ($application->hearings as $hearing) {
            if (! $includeInternal && ! $hearing->candidate_visible) {
                continue;
            }
            $events->push([
                'date' => $hearing->issued_at ?? $hearing->created_at,
                'type' => 'preliminary_hearing_opened',
                'title' => 'Audiência prévia aberta',
                'description' => $hearing->subject,
                'visibility' => TimelineEventVisibility::CandidateVisible->value,
                'due_at' => $hearing->deadline_at,
                'public_status' => null,
            ]);
        }

        foreach ($application->officialNotifications as $notification) {
            $events->push([
                'date' => $notification->sent_at ?? $notification->created_at,
                'type' => 'notification_sent',
                'title' => $notification->subject,
                'description' => 'Notificação registada no centro de notificações.',
                'visibility' => TimelineEventVisibility::CandidateVisible->value,
                'due_at' => $notification->expires_at,
                'public_status' => null,
            ]);
        }

        return $events->sortBy('date')->values();
    }
}
