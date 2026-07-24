<?php

namespace App\Services\Finance;

use App\Enums\FinancialTransactionType;
use App\Enums\LeasePaymentStatus;
use App\Enums\PaymentAllocationStatus;
use App\Enums\RentInstallmentStatus;
use App\Models\LeasePayment;
use App\Models\PaymentAllocation;
use App\Models\RentInstallment;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use App\Support\DecimalMoney;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentAllocationService
{
    public function __construct(
        private readonly FinancialTransactionService $transactions,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function allocate(LeasePayment $payment, RentInstallment $installment, User $actor, int|string|null $amount = null): PaymentAllocation
    {
        return DB::transaction(function () use ($payment, $installment, $actor, $amount) {
            $lockedPayment = LeasePayment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedInstallment = RentInstallment::query()
                ->whereKey($installment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->paymentHasStatus($lockedPayment, [LeasePaymentStatus::Confirmed, LeasePaymentStatus::PartiallyAllocated])) {
                throw ValidationException::withMessages(['payment' => 'Só pagamentos confirmados podem ser imputados.']);
            }

            if ($lockedPayment->tenant_financial_account_id !== $lockedInstallment->tenant_financial_account_id) {
                throw ValidationException::withMessages(['rent_installment_id' => 'A prestação não pertence à mesma conta financeira.']);
            }

            $amount = $amount === null
                ? DecimalMoney::min(
                    (string) $lockedPayment->unallocated_amount,
                    (string) $lockedInstallment->amount_outstanding,
                )
                : DecimalMoney::normalize($amount);

            if (
                ! DecimalMoney::isPositive($amount)
                || DecimalMoney::compare($amount, (string) $lockedPayment->unallocated_amount) === 1
                || DecimalMoney::compare($amount, (string) $lockedInstallment->amount_outstanding) === 1
            ) {
                throw ValidationException::withMessages(['amount' => 'O valor a imputar é inválido.']);
            }

            $allocation = new PaymentAllocation;
            $allocation->forceFill([
                'lease_payment_id' => $payment->id,
                'rent_installment_id' => $installment->id,
                'tenant_financial_account_id' => $lockedPayment->tenant_financial_account_id,
                'lease_contract_id' => $lockedPayment->lease_contract_id,
                'user_id' => $lockedPayment->user_id,
                'status' => PaymentAllocationStatus::Active,
                'amount' => $amount,
                'allocated_at' => now(),
                'created_by' => $actor->id,
            ])->save();

            $installmentPaid = DecimalMoney::add((string) $lockedInstallment->amount_paid, $amount);
            $installmentOutstanding = DecimalMoney::max(
                0,
                DecimalMoney::subtract(
                    DecimalMoney::subtract((string) $lockedInstallment->amount_due, $installmentPaid),
                    (string) $lockedInstallment->amount_waived,
                ),
            );
            $lockedInstallment->forceFill([
                'amount_paid' => $installmentPaid,
                'amount_outstanding' => $installmentOutstanding,
                'status' => DecimalMoney::isPositive($installmentOutstanding) ? RentInstallmentStatus::PartiallyPaid : RentInstallmentStatus::Paid,
                'paid_at' => DecimalMoney::isPositive($installmentOutstanding) ? $lockedInstallment->paid_at : now(),
                'updated_by' => $actor->id,
            ])->save();

            $paymentAllocated = DecimalMoney::add((string) $lockedPayment->allocated_amount, $amount);
            $paymentUnallocated = DecimalMoney::max(
                0,
                DecimalMoney::subtract((string) $lockedPayment->amount, $paymentAllocated),
            );
            $lockedPayment->forceFill([
                'allocated_amount' => $paymentAllocated,
                'unallocated_amount' => $paymentUnallocated,
                'status' => DecimalMoney::isPositive($paymentUnallocated) ? LeasePaymentStatus::PartiallyAllocated : LeasePaymentStatus::Allocated,
            ])->save();

            $this->transactions->record($this->accountForPayment($lockedPayment), FinancialTransactionType::PaymentAllocated, DecimalMoney::negate($amount), $allocation, $actor, 'Pagamento imputado a prestação.');
            $this->auditLogger->record(AuditEvents::UPDATE, $allocation, 'finance', 'payment_allocate', 'Pagamento imputado a prestação de renda.');

            return $allocation->refresh();
        });
    }

    public function allocateOldest(LeasePayment $payment, User $actor): int
    {
        $count = 0;

        while (DecimalMoney::isPositive((string) $payment->refresh()->unallocated_amount)) {
            $installment = $this->accountForPayment($payment)
                ->rentInstallments()
                ->where('amount_outstanding', '>', 0)
                ->whereNotIn('status', [RentInstallmentStatus::Cancelled->value, RentInstallmentStatus::Waived->value])
                ->orderBy('due_date')
                ->first();

            if (! $installment) {
                break;
            }

            $this->allocate($payment, $installment, $actor);
            $count++;
        }

        return $count;
    }

    public function reverse(PaymentAllocation $allocation, User $actor, string $reason): PaymentAllocation
    {
        return DB::transaction(function () use ($allocation, $actor, $reason): PaymentAllocation {
            $lockedAllocation = PaymentAllocation::query()
                ->whereKey($allocation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $this->allocationHasStatus($lockedAllocation, PaymentAllocationStatus::Active)) {
                return $lockedAllocation;
            }

            $installment = RentInstallment::query()
                ->whereKey($this->installmentForAllocation($lockedAllocation)->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $payment = LeasePayment::query()
                ->whereKey($this->paymentForAllocation($lockedAllocation)->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $amount = DecimalMoney::normalize((string) $lockedAllocation->amount);

            $lockedAllocation->forceFill([
                'status' => PaymentAllocationStatus::Reversed,
                'reversed_at' => now(),
                'reversed_by' => $actor->id,
                'notes' => trim(($lockedAllocation->notes ? $lockedAllocation->notes."\n" : '').'Estorno: '.$reason),
            ])->save();

            $installmentPaid = DecimalMoney::max(
                0,
                DecimalMoney::subtract((string) $installment->amount_paid, $amount),
            );
            $installmentOutstanding = DecimalMoney::max(
                0,
                DecimalMoney::subtract(
                    DecimalMoney::subtract((string) $installment->amount_due, $installmentPaid),
                    (string) $installment->amount_waived,
                ),
            );
            $installment->forceFill([
                'amount_paid' => $installmentPaid,
                'amount_outstanding' => $installmentOutstanding,
                'status' => DecimalMoney::isPositive($installmentPaid) ? RentInstallmentStatus::PartiallyPaid : RentInstallmentStatus::Issued,
                'paid_at' => null,
                'updated_by' => $actor->id,
            ])->save();

            $payment->forceFill([
                'allocated_amount' => DecimalMoney::max(
                    0,
                    DecimalMoney::subtract((string) $payment->allocated_amount, $amount),
                ),
                'unallocated_amount' => DecimalMoney::min(
                    (string) $payment->amount,
                    DecimalMoney::add((string) $payment->unallocated_amount, $amount),
                ),
                'status' => LeasePaymentStatus::Confirmed,
            ])->save();

            $this->transactions->recalculateAccount($this->accountForPayment($payment));

            return $lockedAllocation->refresh();
        });
    }

    private function accountForPayment(LeasePayment $payment): TenantFinancialAccount
    {
        $account = $payment->tenantFinancialAccount;

        if (! $account instanceof TenantFinancialAccount) {
            throw ValidationException::withMessages([
                'tenant_financial_account' => 'O pagamento não tem conta financeira associada.',
            ]);
        }

        return $account;
    }

    /**
     * @param  array<int, LeasePaymentStatus>  $statuses
     */
    private function paymentHasStatus(LeasePayment $payment, array $statuses): bool
    {
        $status = $payment->getAttribute('status');

        foreach ($statuses as $expected) {
            if ($status === $expected || $status === $expected->value) {
                return true;
            }
        }

        return false;
    }

    private function installmentForAllocation(PaymentAllocation $allocation): RentInstallment
    {
        $installment = $allocation->rentInstallment;

        if (! $installment instanceof RentInstallment) {
            throw ValidationException::withMessages([
                'rent_installment' => 'A imputação não tem prestação associada.',
            ]);
        }

        return $installment;
    }

    private function paymentForAllocation(PaymentAllocation $allocation): LeasePayment
    {
        $payment = $allocation->leasePayment;

        if (! $payment instanceof LeasePayment) {
            throw ValidationException::withMessages([
                'lease_payment' => 'A imputação não tem pagamento associado.',
            ]);
        }

        return $payment;
    }

    private function allocationHasStatus(PaymentAllocation $allocation, PaymentAllocationStatus $expected): bool
    {
        $status = $allocation->getAttribute('status');

        return $status === $expected || $status === $expected->value;
    }
}
