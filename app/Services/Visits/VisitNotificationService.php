<?php

namespace App\Services\Visits;

use App\Enums\OfficialNotificationChannel;
use App\Enums\OfficialNotificationType;
use App\Models\HousingVisit;
use App\Models\User;
use App\Services\Notifications\OfficialNotificationService;
use Throwable;

class VisitNotificationService
{
    public function __construct(private readonly OfficialNotificationService $notifications) {}

    public function visitScheduled(HousingVisit $visit, User $actor): void
    {
        $this->notifyCandidate(
            $visit,
            $actor,
            OfficialNotificationType::VisitScheduled,
            'Visita solicitada',
            'O seu pedido de visita foi registado e fica sujeito à disponibilidade dos serviços municipais.',
        );
    }

    public function visitConfirmed(HousingVisit $visit, User $actor): void
    {
        $this->notifyCandidate($visit, $actor, OfficialNotificationType::VisitConfirmed, 'Visita confirmada', 'A visita foi confirmada na plataforma.');
    }

    public function visitRescheduled(HousingVisit $visit, User $actor): void
    {
        $this->notifyCandidate($visit, $actor, OfficialNotificationType::VisitRescheduled, 'Visita reagendada', 'A visita foi reagendada na plataforma.');
    }

    public function visitCancelled(HousingVisit $visit, User $actor): void
    {
        $this->notifyCandidate($visit, $actor, OfficialNotificationType::VisitCancelled, 'Visita cancelada', 'A visita foi cancelada e o histórico ficou registado.');
    }

    public function visitCompleted(HousingVisit $visit, User $actor): void
    {
        $this->notifyCandidate($visit, $actor, OfficialNotificationType::VisitCompleted, 'Visita concluída', 'A visita foi marcada como concluída pelos serviços municipais.');
    }

    private function notifyCandidate(HousingVisit $visit, User $actor, OfficialNotificationType $type, string $subject, string $body): void
    {
        $candidate = $visit->candidate;

        if (! $candidate instanceof User) {
            return;
        }

        try {
            $this->notifications->createInternal(
                user: $candidate,
                type: $type,
                subject: $subject,
                body: $body,
                notifiable: $visit,
                application: $visit->application,
                actor: $actor,
                channel: OfficialNotificationChannel::CandidateArea,
                actionUrl: route('candidate.visits.show', $visit, false),
            );
        } catch (Throwable) {
            // Notificações não devem bloquear o fluxo operacional de visitas.
        }
    }
}
