<?php

namespace App\Services\Finance;

use App\Enums\ArrearStatus;
use App\Enums\RegularizationAgreementStatus;
use App\Enums\RegularizationInstallmentStatus;
use App\Enums\RentInstallmentStatus;
use App\Models\Arrear;
use App\Models\RegularizationAgreement;
use App\Models\RegularizationInstallment;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use App\Support\DecimalMoney;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegularizationAgreementService
{
    public function __construct(
        private readonly FinanceNumberService $numbers,
        private readonly FinanceNotificationService $notifications,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(TenantFinancialAccount $account, User $actor, array $data): RegularizationAgreement
    {
        return DB::transaction(function () use ($account, $actor, $data) {
            $lockedAccount = TenantFinancialAccount::query()
                ->whereKey($account->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $arrearIds = $data['arrear_ids'] ?? $lockedAccount->arrears()->whereIn('status', [ArrearStatus::Open->value, ArrearStatus::Notified->value])->pluck('id')->all();
            $arrears = Arrear::query()
                ->whereIn('id', $arrearIds)
                ->where('tenant_financial_account_id', $lockedAccount->id)
                ->whereIn('status', [ArrearStatus::Open->value, ArrearStatus::Notified->value])
                ->lockForUpdate()
                ->get();

            if ($arrears->isEmpty()) {
                throw ValidationException::withMessages(['arrear_ids' => 'Selecione pelo menos um incumprimento da conta.']);
            }

            $totalAmount = DecimalMoney::sum($arrears->pluck('outstanding_amount'));
            $installmentCount = max(1, (int) ($data['installment_count'] ?? 1));
            $start = CarbonImmutable::parse($data['starts_on'] ?? today()->addMonth()->startOfMonth());

            $agreement = new RegularizationAgreement;
            $agreement->forceFill([
                'tenant_financial_account_id' => $lockedAccount->id,
                'lease_contract_id' => $lockedAccount->lease_contract_id,
                'user_id' => $lockedAccount->user_id,
                'agreement_number' => $this->numbers->agreementNumber(),
                'status' => RegularizationAgreementStatus::Proposed,
                'total_amount' => $totalAmount,
                'initial_payment_amount' => $data['initial_payment_amount'] ?? 0,
                'installment_count' => $installmentCount,
                'periodicity' => $data['periodicity'] ?? 'monthly',
                'starts_on' => $start,
                'ends_on' => $start->addMonths($installmentCount - 1),
                'proposed_at' => now(),
                'terms' => $data['terms'] ?? null,
                'legal_basis' => $data['legal_basis'] ?? null,
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ])->save();

            $portion = DecimalMoney::divide($totalAmount, $installmentCount);
            for ($i = 1; $i <= $installmentCount; $i++) {
                $amount = $i === $installmentCount
                    ? DecimalMoney::subtract($totalAmount, DecimalMoney::multiply($portion, $installmentCount - 1))
                    : $portion;
                RegularizationInstallment::query()->create([
                    'regularization_agreement_id' => $agreement->id,
                    'tenant_financial_account_id' => $lockedAccount->id,
                    'lease_contract_id' => $lockedAccount->lease_contract_id,
                    'user_id' => $lockedAccount->user_id,
                    'status' => RegularizationInstallmentStatus::Scheduled,
                    'installment_number' => $i,
                    'due_date' => $start->addMonths($i - 1),
                    'amount_due' => $amount,
                ]);
            }

            $arrears->each(function (Arrear $arrear) use ($agreement, $actor) {
                $arrear->forceFill([
                    'regularization_agreement_id' => $agreement->id,
                    'status' => ArrearStatus::UnderAgreement,
                    'updated_by' => $actor->id,
                ])->save();

                $arrear->rentInstallment?->forceFill(['status' => RentInstallmentStatus::UnderAgreement])->save();
            });

            $this->auditLogger->record(AuditEvents::CREATE, $agreement, 'finance', 'regularization_agreement_create', 'Acordo de regularização criado.');
            $this->notifications->regularizationAgreementCreated($agreement->refresh(), $actor);

            return $agreement->refresh();
        });
    }

    public function approve(RegularizationAgreement $agreement, User $actor): RegularizationAgreement
    {
        return DB::transaction(function () use ($agreement, $actor): RegularizationAgreement {
            $lockedAgreement = RegularizationAgreement::query()
                ->whereKey($agreement->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->agreementHasStatus($lockedAgreement, RegularizationAgreementStatus::Active)) {
                return $lockedAgreement;
            }

            $lockedAgreement->forceFill([
                'status' => RegularizationAgreementStatus::Active,
                'approved_at' => now(),
                'activated_at' => now(),
                'approved_by' => $actor->id,
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::APPROVE, $lockedAgreement, 'finance', 'regularization_agreement_approve', 'Acordo de regularização aprovado e ativado.');

            return $lockedAgreement->refresh();
        });
    }

    public function cancel(RegularizationAgreement $agreement, User $actor, string $reason): RegularizationAgreement
    {
        return DB::transaction(function () use ($agreement, $actor, $reason): RegularizationAgreement {
            $lockedAgreement = RegularizationAgreement::query()
                ->whereKey($agreement->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ($this->agreementHasStatus($lockedAgreement, RegularizationAgreementStatus::Cancelled)) {
                return $lockedAgreement;
            }

            $lockedAgreement->forceFill([
                'status' => RegularizationAgreementStatus::Cancelled,
                'cancelled_at' => now(),
                'internal_notes' => trim(($lockedAgreement->internal_notes ? $lockedAgreement->internal_notes."\n" : '').'Cancelamento: '.$reason),
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogger->record(AuditEvents::UPDATE, $lockedAgreement, 'finance', 'regularization_agreement_cancel', 'Acordo de regularização cancelado.');

            return $lockedAgreement->refresh();
        });
    }

    private function agreementHasStatus(RegularizationAgreement $agreement, RegularizationAgreementStatus $expected): bool
    {
        $status = $agreement->getAttribute('status');

        return $status === $expected || $status === $expected->value;
    }
}
