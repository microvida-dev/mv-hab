<?php

namespace App\Services\TenantBilling;

use App\Enums\ChargeRunStatus;
use App\Enums\ChargeType;
use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\TenantChargeRun;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Support\AuditEvents;
use App\Support\DecimalMoney;
use Illuminate\Support\Facades\DB;

class TenantChargeRunService
{
    public function __construct(
        private readonly TenantInvoiceService $invoices,
        private readonly AuditLogger $auditLogger,
        private readonly MunicipalRecordScopeService $municipalScope,
    ) {}

    public function run(User $actor, int $year, int $month, ChargeType $chargeType = ChargeType::Rent): TenantChargeRun
    {
        return DB::transaction(function () use ($actor, $year, $month, $chargeType) {
            $run = TenantChargeRun::query()
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->where('charge_type', $chargeType->value)
                ->lockForUpdate()
                ->first();

            if (! $run) {
                $run = new TenantChargeRun;
                $run->forceFill([
                    'run_number' => $this->runNumber(),
                    'status' => ChargeRunStatus::Running,
                    'period_year' => $year,
                    'period_month' => $month,
                    'charge_type' => $chargeType,
                    'started_at' => now(),
                    'created_by' => $actor->id,
                ])->save();
            }

            $contracts = $this->municipalScope
                ->contracts(Contract::query(), $actor)
                ->where('status', ContractStatus::Active->value)
                ->whereNotNull('user_id');
            $hasPendingContracts = (clone $contracts)
                ->whereNotIn(
                    'id',
                    $run->items()->select('lease_contract_id'),
                )
                ->exists();

            if (
                $run->status === ChargeRunStatus::Completed
                && ! $hasPendingContracts
            ) {
                return $run;
            }

            if ($run->status !== ChargeRunStatus::Running) {
                $run->forceFill(['status' => ChargeRunStatus::Running, 'started_at' => now()])->save();
            }

            $generated = 0;
            $skipped = 0;
            $total = DecimalMoney::normalize(0);

            $contracts
                ->with('financialAccount')
                ->chunkById(100, function ($contracts) use ($actor, $year, $month, $chargeType, $run, &$generated, &$skipped, &$total): void {
                    foreach ($contracts as $contract) {
                        $before = $contract->tenantInvoices()
                            ->where('period_year', $year)
                            ->where('period_month', $month)
                            ->where('charge_type', $chargeType->value)
                            ->exists();

                        $invoice = $this->invoices->issueForContract($contract, $actor, [
                            'period_year' => $year,
                            'period_month' => $month,
                            'charge_type' => $chargeType->value,
                            'amount' => DecimalMoney::normalize(
                                (string) ($contract->getAttribute('monthly_rent') ?? '0'),
                            ),
                            'notes' => 'Gerada por execução operacional de cobranças.',
                        ]);

                        $alreadyRecorded = $run->items()
                            ->where('lease_contract_id', $invoice->lease_contract_id)
                            ->where('tenant_invoice_id', $invoice->id)
                            ->exists();

                        if ($alreadyRecorded) {
                            continue;
                        }

                        $run->items()->create([
                            'tenant_invoice_id' => $invoice->id,
                            'tenant_financial_account_id' => $invoice->tenant_financial_account_id,
                            'lease_contract_id' => $invoice->lease_contract_id,
                            'user_id' => $invoice->user_id,
                            'housing_unit_id' => $invoice->housing_unit_id,
                            'status' => $before ? 'skipped_existing' : 'generated',
                            'amount' => $invoice->amount_due,
                            'message' => $before ? 'Fatura já existia para o período.' : 'Fatura gerada.',
                        ]);

                        $before ? $skipped++ : $generated++;
                        if (! $before) {
                            $total = DecimalMoney::add($total, (string) $invoice->amount_due);
                        }
                    }
                });

            $run->forceFill([
                'status' => ChargeRunStatus::Completed,
                'completed_at' => now(),
                'generated_count' => $run->items()
                    ->where('status', 'generated')
                    ->count(),
                'skipped_count' => $run->items()
                    ->where('status', 'skipped_existing')
                    ->count(),
                'warning_count' => 0,
                'total_amount' => $run->items()
                    ->where('status', 'generated')
                    ->sum('amount'),
                'warnings' => null,
            ])->save();

            $this->auditLogger->record(
                AuditEvents::CREATE,
                $run,
                'finance',
                'tenant_charge_run_completed',
                'Execução operacional de cobranças concluída.',
                metadata: [
                    'actor_id' => $actor->id,
                    'municipality_id' => $actor->municipality_id,
                    'generated_count' => $generated,
                    'skipped_count' => $skipped,
                    'generated_amount' => $total,
                ],
            );

            return $run->refresh();
        });
    }

    private function runNumber(): string
    {
        return 'TCR-'.now()->format('Ym').'-'.str_pad((string) (TenantChargeRun::query()->withTrashed()->count() + 1), 6, '0', STR_PAD_LEFT);
    }
}
