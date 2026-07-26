<?php

namespace App\Services\Finance;

use App\Enums\FinancialTransactionType;
use App\Enums\RentInstallmentStatus;
use App\Models\RentInstallment;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use App\Support\DecimalMoney;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentInstallmentService
{
    public function __construct(
        private readonly FinancialTransactionService $transactions,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function issue(RentInstallment $installment, User $actor): RentInstallment
    {
        return DB::transaction(function () use ($installment, $actor): RentInstallment {
            $lockedInstallment = RentInstallment::query()
                ->whereKey($installment->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ($lockedInstallment->status === RentInstallmentStatus::Issued) {
                return $lockedInstallment;
            }

            if ($lockedInstallment->status !== RentInstallmentStatus::Scheduled) {
                throw ValidationException::withMessages([
                    'rent_installment' => 'Só prestações agendadas podem ser emitidas.',
                ]);
            }

            $lockedInstallment->forceFill([
                'status' => RentInstallmentStatus::Issued,
                'issued_at' => now(),
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(
                AuditEvents::UPDATE,
                $lockedInstallment,
                'finance',
                'rent_installment_issue',
                'Prestação de renda emitida.',
            );

            return $lockedInstallment->refresh();
        });
    }

    public function waive(RentInstallment $installment, User $actor): RentInstallment
    {
        return DB::transaction(function () use ($installment, $actor): RentInstallment {
            $lockedInstallment = RentInstallment::query()
                ->whereKey($installment->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ($lockedInstallment->status === RentInstallmentStatus::Waived) {
                return $lockedInstallment;
            }

            if (in_array($lockedInstallment->status, [RentInstallmentStatus::Paid, RentInstallmentStatus::Cancelled], true)) {
                throw ValidationException::withMessages([
                    'rent_installment' => 'Uma prestação paga ou cancelada não pode ser dispensada.',
                ]);
            }

            $waivedAmount = DecimalMoney::normalize((string) $lockedInstallment->amount_outstanding);
            $lockedInstallment->forceFill([
                'status' => RentInstallmentStatus::Waived,
                'amount_waived' => DecimalMoney::add((string) $lockedInstallment->amount_waived, $waivedAmount),
                'amount_outstanding' => DecimalMoney::normalize(0),
                'updated_by' => $actor->id,
            ])->save();

            $account = $lockedInstallment->tenantFinancialAccount;
            if (! $account instanceof TenantFinancialAccount) {
                throw ValidationException::withMessages([
                    'tenant_financial_account' => 'A prestação não tem conta financeira associada.',
                ]);
            }

            $this->transactions->record(
                $account,
                FinancialTransactionType::Waiver,
                DecimalMoney::negate($waivedAmount),
                $lockedInstallment,
                $actor,
                'Prestação de renda dispensada.',
            );
            $this->auditLogger->record(
                AuditEvents::UPDATE,
                $lockedInstallment,
                'finance',
                'rent_installment_waive',
                'Prestação de renda dispensada.',
            );

            return $lockedInstallment->refresh();
        });
    }
}
