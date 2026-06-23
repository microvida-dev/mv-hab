<?php

namespace App\Services\Allocation;

use App\Enums\OfficialNotificationType;
use App\Models\Allocation;
use App\Models\AllocationOffer;
use App\Models\Application;
use App\Models\User;
use App\Services\Notifications\OfficialNotificationService;
use Illuminate\Validation\ValidationException;

class AllocationNotificationService
{
    public function __construct(private readonly OfficialNotificationService $notifications) {}

    public function offerIssued(AllocationOffer $offer, User $actor): void
    {
        $this->notify($offer, OfficialNotificationType::AllocationOfferIssued, 'Oferta de atribuição emitida', 'Foi-lhe proposta a atribuição de uma habitação. Consulte a área reservada para responder dentro do prazo indicado.', $actor);
    }

    public function offerAccepted(AllocationOffer $offer, User $actor): void
    {
        $this->notify($offer, OfficialNotificationType::AllocationOfferAccepted, 'Oferta de atribuição aceite', 'A aceitação da oferta foi registada. A fase seguinte corresponde à preparação do contrato.', $actor);
    }

    public function offerRefused(AllocationOffer $offer, User $actor): void
    {
        $this->notify($offer, OfficialNotificationType::AllocationOfferRefused, 'Oferta de atribuição recusada', 'A recusa da oferta foi registada. O Município poderá chamar candidato suplente conforme as regras do procedimento.', $actor);
    }

    public function offerExpired(AllocationOffer $offer, User $actor): void
    {
        $this->notify($offer, OfficialNotificationType::AllocationOfferExpired, 'Oferta de atribuição expirada', 'O prazo de resposta à oferta terminou.', $actor);
    }

    public function reserveCalled(Allocation $allocation, User $actor): void
    {
        $this->notifications->createInternal(
            user: $this->requiredAllocationCandidate($allocation),
            type: OfficialNotificationType::ReserveCandidateCalled,
            subject: 'Chamada de suplente',
            body: 'Foi chamado como suplente no procedimento de atribuição.',
            notifiable: $allocation,
            application: $this->optionalAllocationApplication($allocation),
            actor: $actor,
        );
    }

    public function readyForContract(Allocation $allocation, User $actor): void
    {
        $this->notifications->createInternal(
            user: $this->requiredAllocationCandidate($allocation),
            type: OfficialNotificationType::AllocationReadyForContract,
            subject: 'Atribuição pronta para contrato',
            body: 'A atribuição foi aceite e está preparada para a fase contratual.',
            notifiable: $allocation,
            application: $this->optionalAllocationApplication($allocation),
            actor: $actor,
        );
    }

    private function notify(AllocationOffer $offer, OfficialNotificationType $type, string $subject, string $body, User $actor): void
    {
        $this->notifications->createInternal(
            user: $this->requiredOfferCandidate($offer),
            type: $type,
            subject: $subject,
            body: $body,
            notifiable: $offer,
            application: $this->optionalOfferApplication($offer),
            actor: $actor,
        );
    }

    private function requiredAllocationCandidate(Allocation $allocation): User
    {
        $candidate = $allocation->candidate;

        if (! $candidate instanceof User) {
            throw ValidationException::withMessages(['candidate' => 'A atribuição não tem candidato associado.']);
        }

        return $candidate;
    }

    private function requiredOfferCandidate(AllocationOffer $offer): User
    {
        $candidate = $offer->candidate;

        if (! $candidate instanceof User) {
            throw ValidationException::withMessages(['candidate' => 'A oferta não tem candidato associado.']);
        }

        return $candidate;
    }

    private function optionalAllocationApplication(Allocation $allocation): ?Application
    {
        $application = $allocation->application;

        return $application instanceof Application ? $application : null;
    }

    private function optionalOfferApplication(AllocationOffer $offer): ?Application
    {
        $application = $offer->application;

        return $application instanceof Application ? $application : null;
    }
}
