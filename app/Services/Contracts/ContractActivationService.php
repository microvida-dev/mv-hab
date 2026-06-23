<?php

namespace App\Services\Contracts;

use App\Enums\ContestHousingUnitStatus;
use App\Enums\ContractSignatureStatus;
use App\Enums\ContractStatus;
use App\Enums\ContractValidationStatus;
use App\Enums\DepositStatus;
use App\Enums\HousingUnitStatus;
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
        $contract->loadMissing(['deposit', 'validations', 'signatures', 'housingUnit', 'contestHousingUnit']);

        if (! in_array($contract->status, [ContractStatus::Issued, ContractStatus::Signed], true)) {
            throw ValidationException::withMessages(['contract' => 'O contrato deve estar emitido ou assinado para ativação.']);
        }

        if (! $contract->validations->contains(fn ($validation) => $validation->status === ContractValidationStatus::Approved)) {
            throw ValidationException::withMessages(['validation' => 'A ativação exige validação interna aprovada.']);
        }

        if (! $contract->signatures->contains(fn ($signature) => $signature->status === ContractSignatureStatus::Signed)) {
            throw ValidationException::withMessages(['signature' => 'A ativação exige assinatura ou registo manual assinado.']);
        }

        if ($contract->deposit && (float) $contract->deposit->amount > 0 && ! in_array($contract->deposit->status, [DepositStatus::Paid, DepositStatus::Waived], true)) {
            throw ValidationException::withMessages(['deposit' => 'A caução deve estar paga manualmente ou dispensada antes da ativação.']);
        }

        return DB::transaction(function () use ($contract, $actor, $reason) {
            $active = $this->statusService->transition($contract, ContractStatus::Active, $actor, $reason);

            $active->housingUnit?->forceFill(['status' => HousingUnitStatus::Occupied])->save();
            $active->contestHousingUnit?->forceFill(['status' => ContestHousingUnitStatus::Accepted])->save();

            $this->auditLogger->record(AuditEvents::UPDATE, $active->housingUnit, 'contracts', 'housing_unit_contract_activation', 'Habitação marcada como ocupada por ativação contratual.');
            $this->notificationService->active($active, $actor);

            return $active->refresh();
        });
    }
}
