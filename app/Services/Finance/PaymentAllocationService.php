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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentAllocationService
{
    public function __construct(
        private readonly FinancialTransactionService $transactions,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function allocate(LeasePayment $payment, RentInstallment $installment, User $actor, ?float $amount = null): PaymentAllocation
    {
        return DB::transaction(function () use ($payment, $installment, $actor, $amount) {
            if (! $this->paymentHasStatus($payment, [LeasePaymentStatus::Confirmed, LeasePaymentStatus::PartiallyAllocated])) {
                throw ValidationException::withMessages(['payment' => 'Só pagamentos confirmados podem ser imputados.']);
            }

            if ($payment->tenant_financial_account_id !== $installment->tenant_financial_account_id) {
                throw ValidationException::withMessages(['rent_installment_id' => 'A prestação não pertence à mesma conta financeira.']);
            }

            $amount = $amount ?? min((float) $payment->unallocated_amount, (float) $installment->amount_outstanding);

            if ($amount <= 0 || $amount > (float) $payment->unallocated_amount || $amount > (float) $installment->amount_outstanding) {
                throw ValidationException::withMessages(['amount' => 'O valor a imputar é inválido.']);
            }

            $allocation = new PaymentAllocation;
            $allocation->forceFill([
                'lease_payment_id' => $payment->id,
                'rent_installment_id' => $installment->id,
                'tenant_financial_account_id' => $payment->tenant_financial_account_id,
                'lease_contract_id' => $payment->lease_contract_id,
                'user_id' => $payment->user_id,
                'status' => PaymentAllocationStatus::Active,
                'amount' => $amount,
                'allocated_at' => now(),
                'created_by' => $actor->id,
            ])->save();

            $installmentPaid = (float) $installment->amount_paid + $amount;
            $installmentOutstanding = max(0, (float) $installment->amount_due - $installmentPaid - (float) $installment->amount_waived);
            $installment->forceFill([
                'amount_paid' => $installmentPaid,
                'amount_outstanding' => $installmentOutstanding,
                'status' => $installmentOutstanding <= 0 ? RentInstallmentStatus::Paid : RentInstallmentStatus::PartiallyPaid,
                'paid_at' => $installmentOutstanding <= 0 ? now() : $installment->paid_at,
                'updated_by' => $actor->id,
            ])->save();

            $paymentAllocated = (float) $payment->allocated_amount + $amount;
            $paymentUnallocated = max(0, (float) $payment->amount - $paymentAllocated);
            $payment->forceFill([
                'allocated_amount' => $paymentAllocated,
                'unallocated_amount' => $paymentUnallocated,
                'status' => $paymentUnallocated <= 0 ? LeasePaymentStatus::Allocated : LeasePaymentStatus::PartiallyAllocated,
            ])->save();

            $this->transactions->record($this->accountForPayment($payment), FinancialTransactionType::PaymentAllocated, (float) $amount * -1, $allocation, $actor, 'Pagamento imputado a prestação.');
            $this->auditLogger->record(AuditEvents::UPDATE, $allocation, 'finance', 'payment_allocate', 'Pagamento imputado a prestação de renda.');

            return $allocation->refresh();
        });
    }

    public function allocateOldest(LeasePayment $payment, User $actor): int
    {
        $count = 0;

        while ((float) $payment->refresh()->unallocated_amount > 0) {
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
        if (! $this->allocationHasStatus($allocation, PaymentAllocationStatus::Active)) {
            return $allocation;
        }

        $installment = $this->installmentForAllocation($allocation);
        $payment = $this->paymentForAllocation($allocation);
        $amount = (float) $allocation->amount;

        $allocation->forceFill([
            'status' => PaymentAllocationStatus::Reversed,
            'reversed_at' => now(),
            'reversed_by' => $actor->id,
            'notes' => trim(($allocation->notes ? $allocation->notes."\n" : '').'Estorno: '.$reason),
        ])->save();

        $installmentPaid = max(0, (float) $installment->amount_paid - $amount);
        $installmentOutstanding = max(0, (float) $installment->amount_due - $installmentPaid - (float) $installment->amount_waived);
        $installment->forceFill([
            'amount_paid' => $installmentPaid,
            'amount_outstanding' => $installmentOutstanding,
            'status' => $installmentPaid > 0 ? RentInstallmentStatus::PartiallyPaid : RentInstallmentStatus::Issued,
            'paid_at' => null,
            'updated_by' => $actor->id,
        ])->save();

        $payment->forceFill([
            'allocated_amount' => max(0, (float) $payment->allocated_amount - $amount),
            'unallocated_amount' => min((float) $payment->amount, (float) $payment->unallocated_amount + $amount),
            'status' => LeasePaymentStatus::Confirmed,
        ])->save();

        $this->transactions->recalculateAccount($this->accountForPayment($payment));

        return $allocation->refresh();
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
