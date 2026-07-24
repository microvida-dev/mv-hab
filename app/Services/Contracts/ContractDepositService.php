<?php

namespace App\Services\Contracts;

use App\Enums\DepositStatus;
use App\Models\Contract;
use App\Models\ContractDeposit;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use App\Support\DecimalMoney;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractDepositService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly ContractNotificationService $notificationService,
    ) {}

    public function createForContract(Contract $contract, User $actor): ContractDeposit
    {
        return DB::transaction(function () use ($contract, $actor): ContractDeposit {
            $lockedContract = Contract::query()->whereKey($contract->getKey())->lockForUpdate()->firstOrFail();
            $existing = ContractDeposit::query()
                ->where('lease_contract_id', $lockedContract->id)
                ->lockForUpdate()
                ->first();

            if ($existing instanceof ContractDeposit) {
                return $existing;
            }

            $amount = DecimalMoney::normalize(
                $lockedContract->deposit_amount === null ? null : (string) $lockedContract->deposit_amount,
            );
            $deposit = ContractDeposit::query()->create([
                'lease_contract_id' => $lockedContract->id,
                'application_id' => $lockedContract->application_id,
                'allocation_id' => $lockedContract->allocation_id,
                'user_id' => $lockedContract->user_id,
                'amount' => $amount,
                'currency' => 'EUR',
                'calculation_basis' => 'Caução calculada a partir da regra de renda aplicada. Sem cobrança real nesta sprint.',
            ]);
            $deposit->forceFill([
                'status' => DecimalMoney::isPositive($amount) ? DepositStatus::Pending : DepositStatus::NotRequired,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::CREATE, $deposit, 'contracts', 'contract_deposit_create', 'Caução associada ao contrato.');

            return $deposit->refresh();
        });
    }

    public function markRequested(ContractDeposit $deposit, User $actor): ContractDeposit
    {
        return DB::transaction(function () use ($deposit, $actor): ContractDeposit {
            $lockedDeposit = $this->locked($deposit);
            if ($lockedDeposit->status === DepositStatus::Requested) {
                return $lockedDeposit;
            }

            $lockedDeposit->forceFill([
                'status' => DepositStatus::Requested,
                'requested_at' => now(),
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::UPDATE, $lockedDeposit, 'contracts', 'contract_deposit_requested', 'Caução marcada como solicitada.');
            $this->notificationService->depositRequested($this->leaseContractForDeposit($lockedDeposit), $actor);

            return $lockedDeposit->refresh();
        });
    }

    /**
     * @param  array<string, bool|int|string|null>  $data
     */
    public function markPaid(ContractDeposit $deposit, User $actor, array $data): ContractDeposit
    {
        return DB::transaction(function () use ($deposit, $actor, $data): ContractDeposit {
            $lockedDeposit = $this->locked($deposit);
            if ($lockedDeposit->status === DepositStatus::Paid) {
                return $lockedDeposit;
            }

            $lockedDeposit->forceFill([
                'status' => DepositStatus::Paid,
                'paid_at' => $data['paid_at'],
                'payment_reference' => $data['payment_reference'] ?? null,
                'receipt_reference' => $data['receipt_reference'] ?? null,
                'notes' => $data['notes'] ?? $lockedDeposit->notes,
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::UPDATE, $lockedDeposit, 'contracts', 'contract_deposit_paid_manual', 'Caução registada manualmente como paga.');
            $this->notificationService->depositPaidRegistered($this->leaseContractForDeposit($lockedDeposit), $actor);

            return $lockedDeposit->refresh();
        });
    }

    public function waive(ContractDeposit $deposit, User $actor, string $reason, ?string $internalNotes = null): ContractDeposit
    {
        return DB::transaction(function () use ($deposit, $actor, $reason, $internalNotes): ContractDeposit {
            $lockedDeposit = $this->locked($deposit);
            if ($lockedDeposit->status === DepositStatus::Waived) {
                return $lockedDeposit;
            }

            $lockedDeposit->forceFill([
                'status' => DepositStatus::Waived,
                'waived_at' => now(),
                'notes' => $reason,
                'internal_notes' => $internalNotes,
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::UPDATE, $lockedDeposit, 'contracts', 'contract_deposit_waive', 'Caução dispensada com justificação.');

            return $lockedDeposit->refresh();
        });
    }

    public function cancel(ContractDeposit $deposit, User $actor, ?string $reason = null): ContractDeposit
    {
        return DB::transaction(function () use ($deposit, $actor, $reason): ContractDeposit {
            $lockedDeposit = $this->locked($deposit);
            if ($lockedDeposit->status === DepositStatus::Cancelled) {
                return $lockedDeposit;
            }

            if ($lockedDeposit->status === DepositStatus::Paid) {
                throw ValidationException::withMessages([
                    'contract_deposit' => 'Uma caução paga não pode ser cancelada sem operação financeira própria.',
                ]);
            }

            $lockedDeposit->forceFill([
                'status' => DepositStatus::Cancelled,
                'cancelled_at' => now(),
                'internal_notes' => $reason ?: $lockedDeposit->internal_notes,
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::UPDATE, $lockedDeposit, 'contracts', 'contract_deposit_cancel', 'Caução cancelada.');

            return $lockedDeposit->refresh();
        });
    }

    private function locked(ContractDeposit $deposit): ContractDeposit
    {
        return ContractDeposit::query()->whereKey($deposit->getKey())->lockForUpdate()->firstOrFail();
    }

    private function leaseContractForDeposit(ContractDeposit $deposit): Contract
    {
        $contract = $deposit->leaseContract;

        if (! $contract instanceof Contract) {
            throw ValidationException::withMessages([
                'contract' => 'A caução não tem contrato associado.',
            ]);
        }

        return $contract;
    }
}
