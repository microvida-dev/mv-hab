<?php

namespace App\Services\TenantTransition;

use App\Enums\ContractStatus;
use App\Enums\TenantTransitionStatus;
use App\Models\Allocation;
use App\Models\Contract;
use App\Models\TenantTransition;
use App\Models\User;
use App\Models\WinnerRegistration;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;

class TenantTransitionService
{
    public function __construct(
        private readonly TenantTransitionValidator $validator,
        private readonly TenantAccessProvisioningService $provisioning,
        private readonly AuditLogger $audit,
    ) {}

    public function run(WinnerRegistration $winner, User $actor): TenantTransition
    {
        return DB::transaction(function () use ($winner, $actor): TenantTransition {
            $winner->loadMissing('latestKeyHandoverAppointment');
            $validation = $this->validator->validate($winner);
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
            $account = $validation['blocked'] ? null : $this->provisioning->provision($contract, $actor);
            $allocationHousingUnitId = $winner->allocation_id === null
                ? null
                : Allocation::query()->whereKey($winner->allocation_id)->value('housing_unit_id');

            $transition = TenantTransition::query()->firstOrNew([
                'winner_registration_id' => $winner->id,
                'application_id' => $winner->application_id,
            ]);

            $transition->fill([
                'key_handover_appointment_id' => $winner->latestKeyHandoverAppointment?->id,
                'allocation_id' => $winner->allocation_id,
                'lease_contract_id' => $contract?->id,
                'tenant_financial_account_id' => $account?->id,
                'user_id' => $winner->user_id,
                'housing_unit_id' => $winner->housing_unit_id ?? ($allocationHousingUnitId === null ? null : (int) $allocationHousingUnitId),
                'preconditions' => $validation['preconditions'],
                'warnings' => $validation['warnings'],
                'metadata' => ['source' => 'sprint_25_transition'],
            ]);

            $transition->forceFill([
                'status' => $validation['blocked'] ? TenantTransitionStatus::Blocked : TenantTransitionStatus::Completed,
                'blocked_at' => $validation['blocked'] ? now() : null,
                'blocked_reason' => $validation['blocked'] ? 'Pré-condições críticas por cumprir.' : null,
                'completed_at' => $validation['blocked'] ? null : now(),
                'completed_by' => $validation['blocked'] ? null : $actor->id,
            ])->save();

            $this->audit->record(AuditEvents::UPDATE, $transition, 'allocations', 'tenant_transition_run', 'Transição para área de inquilino processada.');

            return $transition->refresh();
        });
    }
}
