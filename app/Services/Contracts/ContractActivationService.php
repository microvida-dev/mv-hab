<?php

namespace App\Services\Contracts;

use App\Enums\ContestHousingUnitStatus;
use App\Enums\ContractSignatureStatus;
use App\Enums\ContractStatus;
use App\Enums\ContractValidationStatus;
use App\Enums\DepositStatus;
use App\Enums\HousingUnitStatus;
use App\Enums\RegulatoryClassificationStatus;
use App\Models\Contract;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractActivationService
{
    public function __construct(
        private readonly LeaseContractStatusService $statusService,
        private readonly ContractNotificationService $notificationService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function activate(Contract $contract, User $actor, ?string $reason = null): Contract
    {
        return DB::transaction(function () use ($contract, $actor, $reason) {
            /** @var Contract $locked */
            $locked = Contract::query()
                ->lockForUpdate()
                ->with([
                    'deposit',
                    'validations',
                    'signatures',
                    'housingUnit',
                    'contestHousingUnit',
                ])
                ->findOrFail($contract->getKey());

            if ($locked->status === ContractStatus::Active) {
                return $locked;
            }

            if (! in_array($locked->status, [
                ContractStatus::Issued,
                ContractStatus::Signed,
                ContractStatus::Suspended,
            ], true)) {
                throw ValidationException::withMessages([
                    'contract' => 'O contrato deve estar emitido, assinado ou suspenso para ativação.',
                ]);
            }

            if (
                $locked->regulatory_classification_status === RegulatoryClassificationStatus::Configured
                && $locked->regulatory_snapshot_id === null
            ) {
                throw ValidationException::withMessages([
                    'contract' => 'O contrato configurado não possui snapshot regulamentar e não pode ser ativado.',
                ]);
            }

            if ($locked->regulatory_classification_status === RegulatoryClassificationStatus::RequiresManualReview) {
                throw ValidationException::withMessages([
                    'contract' => 'A classificação regulamentar do contrato requer revisão antes da ativação.',
                ]);
            }

            if (! $locked->validations->contains(
                fn ($validation) => $validation->status === ContractValidationStatus::Approved,
            )) {
                throw ValidationException::withMessages([
                    'validation' => 'A ativação exige validação interna aprovada.',
                ]);
            }

            if (! $locked->signatures->contains(
                fn ($signature) => $signature->status === ContractSignatureStatus::Signed,
            )) {
                throw ValidationException::withMessages([
                    'signature' => 'A ativação exige assinatura ou registo manual assinado.',
                ]);
            }

            if (
                $locked->deposit
                && (float) $locked->deposit->amount > 0
                && ! in_array(
                    $locked->deposit->status,
                    [DepositStatus::Paid, DepositStatus::Waived],
                    true,
                )
            ) {
                throw ValidationException::withMessages([
                    'deposit' => 'A caução deve estar paga manualmente ou dispensada antes da ativação.',
                ]);
            }

            $active = $this->statusService->transition(
                $locked,
                ContractStatus::Active,
                $actor,
                $reason,
            );

            $active->housingUnit?->forceFill(['status' => HousingUnitStatus::Occupied])->save();
            $active->contestHousingUnit?->forceFill(['status' => ContestHousingUnitStatus::Accepted])->save();

            $this->auditLogger->record(
                AuditEvents::UPDATE,
                $active->housingUnit,
                'contracts',
                'housing_unit_contract_activation',
                'Habitação marcada como ocupada por ativação contratual.',
                metadata: ['actor_id' => $actor->id],
            );
            $this->notificationService->active($active, $actor);

            return $active->refresh();
        });
    }
}
