<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Enums\KeyHandoverStatus;
use App\Models\Application;
use App\Models\KeyHandoverAppointment;
use App\Models\User;
use App\Services\Dashboard\Timeline\BaseTimelineProvider;
use App\Services\Dashboard\Timeline\TimelineEventFactory;

class KeyHandoverTimelineProvider extends BaseTimelineProvider
{
    public function __construct(
        private readonly TimelineEventFactory $factory = new TimelineEventFactory,
    ) {}

    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('contracts.view')) {
            return [];
        }

        return KeyHandoverAppointment::query()
            ->with('application')
            ->whereNotNull('scheduled_for')
            ->whereNotIn('status', [
                KeyHandoverStatus::Completed->value,
                KeyHandoverStatus::Cancelled->value,
            ])
            ->orderBy('scheduled_for')
            ->limit(20)
            ->get()
            ->map(fn (KeyHandoverAppointment $appointment): TimelineEvent => $this->appointmentEvent($appointment))
            ->all();
    }

    private function appointmentEvent(KeyHandoverAppointment $appointment): TimelineEvent
    {
        $application = $appointment->application;
        $applicationNumber = $application instanceof Application
            ? $application->application_number
            : null;
        $reference = $applicationNumber ?? 'Candidatura #'.$appointment->application_id;

        return $this->factory->make(
            id: 'key-handover-'.$appointment->getKey(),
            type: TimelineType::KeyHandover,
            title: 'Entrega de chave agendada',
            description: trim($reference.' · '.$appointment->location),
            route: route('backoffice.key-handovers.index'),
            datetime: $appointment->scheduled_for,
            priority: $appointment->scheduled_for?->isPast()
                ? TimelinePriority::High
                : TimelinePriority::Medium,
            icon: 'key',
            tone: $appointment->scheduled_for?->isPast() ? 'warning' : 'info',
            workspace: TimelineWorkspace::Tenant,
            metadata: [
                'key_handover_appointment_id' => $appointment->getKey(),
                'application_id' => $appointment->application_id,
                'application_number' => $applicationNumber,
                'status' => $appointment->status->value,
                'location' => $appointment->location,
            ],
        );
    }
}
