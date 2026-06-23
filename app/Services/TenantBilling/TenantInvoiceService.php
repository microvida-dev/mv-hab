<?php

namespace App\Services\TenantBilling;

use App\Enums\ChargeType;
use App\Enums\TenantInvoiceStatus;
use App\Models\Contract;
use App\Models\RentInstallment;
use App\Models\TenantInvoice;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Finance\TenantFinancialAccountService;
use App\Support\AuditEvents;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TenantInvoiceService
{
    public function __construct(
        private readonly TenantFinancialAccountService $accounts,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    public function issueForContract(Contract $contract, User $actor, array $data): TenantInvoice
    {
        return DB::transaction(function () use ($contract, $actor, $data) {
            $amount = (float) ($data['amount'] ?? $contract->monthly_rent);

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'O valor da fatura tem de ser superior a zero.']);
            }

            $account = $contract->financialAccount ?: $this->accounts->ensureForContract($contract, $actor);
            $periodYear = (int) ($data['period_year'] ?? now()->year);
            $periodMonth = (int) ($data['period_month'] ?? now()->month);
            $chargeType = $this->chargeTypeFromData($data);

            $invoice = TenantInvoice::query()->firstOrNew([
                'tenant_financial_account_id' => $account->id,
                'period_year' => $periodYear,
                'period_month' => $periodMonth,
                'charge_type' => $chargeType->value,
            ]);

            if ($invoice->exists && ! $this->invoiceHasStatus($invoice, [TenantInvoiceStatus::Draft, TenantInvoiceStatus::UnderReview])) {
                return $invoice;
            }

            $invoice->forceFill([
                'lease_contract_id' => $contract->id,
                'user_id' => $contract->user_id,
                'housing_unit_id' => $contract->housing_unit_id,
                'invoice_number' => $invoice->invoice_number ?: $this->invoiceNumber(),
                'status' => TenantInvoiceStatus::Issued,
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? now()->startOfMonth()->addDays((int) ($contract->payment_day ?? 8))->toDateString(),
                'original_amount' => $amount,
                'amount_due' => $amount,
                'amount_paid' => (float) ($invoice->amount_paid ?? 0),
                'amount_outstanding' => max(0, $amount - (float) ($invoice->amount_paid ?? 0)),
                'currency' => $data['currency'] ?? 'EUR',
                'issued_at' => now(),
                'notes' => $data['notes'] ?? $invoice->notes,
                'internal_notes' => $data['internal_notes'] ?? $invoice->internal_notes,
                'created_by' => $invoice->created_by ?: $actor->id,
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::CREATE, $invoice, 'finance', 'tenant_invoice_issued', 'Fatura operacional do inquilino emitida.');

            return $invoice->refresh();
        });
    }

    public function issueFromInstallment(RentInstallment $installment, User $actor): TenantInvoice
    {
        $contract = $this->contractForInstallment($installment);

        $invoice = $this->issueForContract($contract, $actor, [
            'amount' => $installment->amount_due,
            'period_year' => $installment->period_year,
            'period_month' => $installment->period_month,
            'issue_date' => $this->dateString($installment, 'issue_date') ?? now()->toDateString(),
            'due_date' => $this->dateString($installment, 'due_date'),
            'charge_type' => ChargeType::Rent->value,
            'notes' => 'Gerada a partir da prestação '.$installment->reference.'.',
        ]);

        $invoice->forceFill(['source_rent_installment_id' => $installment->id])->save();

        return $invoice->refresh();
    }

    public function markPaymentImpact(TenantInvoice $invoice): TenantInvoice
    {
        $paid = (float) $invoice->payments()
            ->whereIn('status', ['confirmed', 'reconciled'])
            ->sum('allocated_amount');

        $outstanding = max(0, (float) $invoice->amount_due - $paid);
        $status = match (true) {
            $outstanding <= 0 => TenantInvoiceStatus::Paid,
            $paid > 0 => TenantInvoiceStatus::PartiallyPaid,
            $this->isPastDate($invoice, 'due_date') => TenantInvoiceStatus::Overdue,
            default => $invoice->status,
        };

        $invoice->forceFill([
            'amount_paid' => $paid,
            'amount_outstanding' => $outstanding,
            'status' => $status,
            'paid_at' => $outstanding <= 0 ? now() : null,
        ])->save();

        return $invoice->refresh();
    }

    /**
     * @return Builder<TenantInvoice>
     */
    public function tenantScope(User $tenant): Builder
    {
        return TenantInvoice::query()->where('user_id', $tenant->id);
    }

    private function invoiceNumber(): string
    {
        return 'TINV-'.now()->format('Ym').'-'.str_pad((string) (TenantInvoice::query()->withTrashed()->count() + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $data
     */
    private function chargeTypeFromData(array $data): ChargeType
    {
        $value = $data['charge_type'] ?? ChargeType::Rent->value;

        if (! is_int($value) && ! is_string($value)) {
            throw ValidationException::withMessages([
                'charge_type' => 'Tipo de cobrança inválido.',
            ]);
        }

        return ChargeType::from($value);
    }

    /**
     * @param  array<int, TenantInvoiceStatus>  $statuses
     */
    private function invoiceHasStatus(TenantInvoice $invoice, array $statuses): bool
    {
        $status = $invoice->getAttribute('status');

        foreach ($statuses as $expected) {
            if ($status === $expected || $status === $expected->value) {
                return true;
            }
        }

        return false;
    }

    private function dateString(RentInstallment $installment, string $attribute): ?string
    {
        $date = $installment->getAttribute($attribute);

        if ($date instanceof CarbonInterface) {
            return $date->toDateString();
        }

        return is_string($date) && $date !== '' ? $date : null;
    }

    private function isPastDate(TenantInvoice $invoice, string $attribute): bool
    {
        $date = $invoice->getAttribute($attribute);

        return $date instanceof CarbonInterface
            ? $date->isPast()
            : is_string($date) && $date !== '' && Carbon::parse($date)->isPast();
    }

    private function contractForInstallment(RentInstallment $installment): Contract
    {
        $contract = $installment->leaseContract;

        if (! $contract instanceof Contract) {
            throw ValidationException::withMessages([
                'contract' => 'A prestação não tem contrato associado.',
            ]);
        }

        return $contract;
    }
}
