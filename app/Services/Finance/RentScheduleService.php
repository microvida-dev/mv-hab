<?php

namespace App\Services\Finance;

use App\Enums\FinancialTransactionType;
use App\Enums\RentInstallmentStatus;
use App\Enums\RentScheduleStatus;
use App\Models\Contract;
use App\Models\RentInstallment;
use App\Models\RentSchedule;
use App\Models\TenantFinancialAccount;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentScheduleService
{
    public function __construct(
        private readonly TenantFinancialAccountService $accounts,
        private readonly FinanceNumberService $numbers,
        private readonly FinancialTransactionService $transactions,
        private readonly FinanceNotificationService $notifications,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function generateForContract(Contract $contract, User $actor, array $data = []): RentSchedule
    {
        return DB::transaction(function () use ($contract, $actor, $data) {
            $account = $this->accounts->ensureForContract($contract, $actor);

            $start = CarbonImmutable::parse($data['starts_on'] ?? $contract->start_date ?? now()->startOfMonth())->startOfMonth();
            $end = isset($data['ends_on'])
                ? CarbonImmutable::parse($data['ends_on'])->startOfMonth()
                : ($contract->end_date ? CarbonImmutable::parse($contract->end_date)->startOfMonth() : $start->addMonths(11));
            $monthlyRent = (float) ($data['monthly_rent'] ?? $contract->monthly_rent ?? 0);

            if ($monthlyRent <= 0) {
                throw ValidationException::withMessages(['monthly_rent' => 'A renda mensal tem de ser superior a zero.']);
            }

            $account->rentSchedules()->where('status', RentScheduleStatus::Active)->update([
                'status' => RentScheduleStatus::Closed,
                'updated_by' => $actor->id,
                'updated_at' => now(),
            ]);

            $schedule = new RentSchedule;
            $schedule->forceFill([
                'tenant_financial_account_id' => $account->id,
                'lease_contract_id' => $contract->id,
                'user_id' => $contract->user_id,
                'status' => RentScheduleStatus::Active,
                'schedule_type' => $data['schedule_type'] ?? 'initial',
                'starts_on' => $start,
                'ends_on' => $end,
                'monthly_rent' => $monthlyRent,
                'payment_day' => (int) ($data['payment_day'] ?? $contract->payment_day ?? 8),
                'issue_day' => (int) ($data['issue_day'] ?? 1),
                'due_grace_days' => (int) ($data['due_grace_days'] ?? 0),
                'source_rent_review_id' => $data['source_rent_review_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ])->save();

            $count = $this->generateInstallments($account, $schedule, $actor);
            $schedule->forceFill(['generated_installments_count' => $count])->save();

            $this->auditLogger->record(AuditEvents::CREATE, $schedule, 'finance', 'rent_schedule_generate', 'Plano de rendas gerado.', metadata: ['installments' => $count]);
            $this->notifications->rentScheduleGenerated($schedule->refresh(), $actor);
            $this->transactions->recalculateAccount($account);

            return $schedule->refresh();
        });
    }

    private function generateInstallments(TenantFinancialAccount $account, RentSchedule $schedule, User $actor): int
    {
        $period = CarbonImmutable::parse($schedule->starts_on);
        $end = CarbonImmutable::parse($schedule->ends_on ?? $period->addMonths(11));
        $count = 0;

        while ($period <= $end) {
            $dueDay = min((int) $schedule->payment_day, $period->daysInMonth);
            $issueDay = min((int) $schedule->issue_day, $period->daysInMonth);
            $dueDate = $period->day($dueDay)->addDays((int) $schedule->due_grace_days);
            $reference = $this->numbers->rentInstallmentReference($schedule->lease_contract_id, (int) $period->year, (int) $period->month);

            $installment = RentInstallment::query()->firstOrNew(['reference' => $reference]);
            $installment->forceFill([
                'tenant_financial_account_id' => $account->id,
                'rent_schedule_id' => $schedule->id,
                'lease_contract_id' => $schedule->lease_contract_id,
                'user_id' => $schedule->user_id,
                'reference' => $reference,
                'status' => RentInstallmentStatus::Issued,
                'period_year' => (int) $period->year,
                'period_month' => (int) $period->month,
                'issue_date' => $period->day($issueDay),
                'due_date' => $dueDate,
                'original_amount' => $schedule->monthly_rent,
                'amount_due' => $schedule->monthly_rent,
                'amount_paid' => $installment->exists ? $installment->amount_paid : 0,
                'amount_outstanding' => $installment->exists ? $installment->amount_outstanding : $schedule->monthly_rent,
                'currency' => 'EUR',
                'issued_at' => now(),
                'created_by' => $installment->exists ? $installment->created_by : $actor->id,
                'updated_by' => $actor->id,
            ])->save();

            $this->transactions->record($account, FinancialTransactionType::InstallmentIssued, (float) $installment->amount_due, $installment, $actor, 'Prestação de renda emitida.');
            $count++;
            $period = $period->addMonth();
        }

        return $count;
    }
}
