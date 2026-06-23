<?php

namespace App\Services\TenantTransition;

use App\Enums\ContractStatus;
use App\Models\Allocation;
use App\Models\Contract;
use App\Models\WinnerRegistration;

class TenantTransitionValidator
{
    /**
     * @return array{blocked:bool, preconditions:array<string, bool>, warnings:list<string>}
     */
    public function validate(WinnerRegistration $winner): array
    {
        $winner->loadMissing('latestKeyHandoverAppointment');

        $allocationExists = $winner->allocation_id !== null
            && Allocation::query()->whereKey($winner->allocation_id)->exists();
        $allocationHousingUnitId = $winner->allocation_id === null
            ? null
            : Allocation::query()->whereKey($winner->allocation_id)->value('housing_unit_id');
        $contract = $winner->allocation_id === null
            ? null
            : Contract::query()
                ->where('allocation_id', $winner->allocation_id)
                ->whereIn('status', [
                    ContractStatus::Preparation->value,
                    ContractStatus::Issued->value,
                    ContractStatus::Signed->value,
                    ContractStatus::Active->value,
                ])
                ->latest('id')
                ->first();
        $contractStatus = $contract === null
            ? null
            : ContractStatus::tryFrom((string) $contract->getRawOriginal('status'));
        $handover = $winner->latestKeyHandoverAppointment;

        $preconditions = [
            'winner_active' => $winner->status === 'active',
            'allocation_exists' => $allocationExists,
            'housing_unit_exists' => $winner->housing_unit_id !== null || $allocationHousingUnitId !== null,
            'contract_active_or_pending' => $contract === null || in_array($contractStatus, [ContractStatus::Preparation, ContractStatus::Issued, ContractStatus::Signed, ContractStatus::Active], true),
            'key_handover_known' => $handover !== null,
        ];

        $warnings = [];

        if ($contract === null) {
            $warnings[] = 'Contrato ainda não associado; transição fica registada sem conta financeira ativa.';
        }

        if ($handover === null) {
            $warnings[] = 'Entrega de chaves ainda não agendada/concluída.';
        }

        return [
            'blocked' => ! $preconditions['winner_active'] || ! $preconditions['allocation_exists'] || ! $preconditions['housing_unit_exists'],
            'preconditions' => $preconditions,
            'warnings' => $warnings,
        ];
    }
}
