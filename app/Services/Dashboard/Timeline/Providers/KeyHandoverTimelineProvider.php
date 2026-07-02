<?php

namespace App\Services\Dashboard\Timeline\Providers;

use App\Data\Dashboard\TimelineEvent;
use App\Enums\Dashboard\Timeline\TimelinePriority;
use App\Enums\Dashboard\Timeline\TimelineType;
use App\Enums\Dashboard\Timeline\TimelineWorkspace;
use App\Models\KeyHandoverAppointment;
use App\Models\User;
use App\Services\Dashboard\Timeline\TimelineProviderInterface;

class KeyHandoverTimelineProvider implements TimelineProviderInterface
{
    public function forUser(User $user, array $dashboard = []): array
    {
        if (! $user->hasPermission('contracts.view')) {
            return [];
        }

        return KeyHandoverAppointment::query()
            ->whereNotNull('scheduled_for')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('scheduled_for')
            ->limit(20)
            ->get()
            ->map(fn (KeyHandoverAppointment $appointment): TimelineEvent => new TimelineEvent(
                id: 'key-handover-'.$appointment->getKey(),
                type: TimelineType::KeyHandover,
                title: 'Entrega de chave agendada',
                description: trim(($appointment->appointment_number ?? 'Entrega de chave').' · '.$appointment->location),
                route: route('backoffice.key-handovers.show', ['keyHandoverAppointment' => $appointment->getKey()]),
                datetime: $appointment->scheduled_for,
                priority: $appointment->scheduled_for?->isPast()
                    ? TimelinePriority::High
                    : TimelinePriority::Medium,
                icon: 'key',
                tone: $appointment->scheduled_for?->isPast() ? 'warning' : 'info',
                workspace: TimelineWorkspace::Tenant,
                metadata: [
                    'key_handover_appointment_id' => $appointment->getKey(),
                    'appointment_number' => $appointment->appointment_number,
                    'status' => $appointment->status,
                    'location' => $appointment->location,
                ],
            ))
            ->all();
    }
}
