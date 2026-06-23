<?php

namespace App\Services\Contracts;

use App\Enums\OfficialNotificationType;
use App\Models\Contract;
use App\Models\User;
use App\Services\Notifications\OfficialNotificationService;

class ContractNotificationService
{
    public function __construct(private readonly OfficialNotificationService $notifications) {}

    public function preparationStarted(Contract $contract, User $actor): void
    {
        $this->notify($contract, OfficialNotificationType::ContractPreparationStarted, 'Contrato em preparação', 'Os serviços municipais iniciaram a preparação do contrato de arrendamento.', $actor);
    }

    public function issued(Contract $contract, User $actor): void
    {
        $this->notify($contract, OfficialNotificationType::ContractIssued, 'Contrato emitido', 'O contrato foi emitido pelos serviços municipais e está disponível para consulta.', $actor);
    }

    public function signed(Contract $contract, User $actor): void
    {
        $this->notify($contract, OfficialNotificationType::ContractSigned, 'Contrato assinado', 'Foi registada assinatura ou validação manual do contrato.', $actor);
    }

    public function active(Contract $contract, User $actor): void
    {
        $this->notify($contract, OfficialNotificationType::ContractActive, 'Contrato ativo', 'O contrato foi ativado pelos serviços municipais.', $actor);
    }

    public function depositRequested(Contract $contract, User $actor): void
    {
        $this->notify($contract, OfficialNotificationType::DepositRequested, 'Caução solicitada', 'Foi registado pedido de caução associado ao contrato.', $actor);
    }

    public function depositPaidRegistered(Contract $contract, User $actor): void
    {
        $this->notify($contract, OfficialNotificationType::DepositPaidRegistered, 'Caução registada', 'O pagamento da caução foi registado manualmente pelos serviços municipais.', $actor);
    }

    private function notify(Contract $contract, OfficialNotificationType $type, string $subject, string $body, User $actor): void
    {
        if (! $contract->candidate) {
            return;
        }

        $this->notifications->createInternal(
            user: $contract->candidate,
            type: $type,
            subject: $subject,
            body: $body,
            notifiable: $contract,
            application: $contract->application,
            actor: $actor,
        );
    }
}
