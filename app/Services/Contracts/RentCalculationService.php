<?php

namespace App\Services\Contracts;

use App\Enums\AllocationStatus;
use App\Enums\RentCalculationMethod;
use App\Enums\RentCalculationResult;
use App\Enums\RentCalculationStatus;
use App\Models\Allocation;
use App\Models\RentCalculation;
use App\Models\RentRuleSet;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use App\Support\DecimalMoney;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentCalculationService
{
    public function __construct(
        private readonly RentRuleSetResolver $ruleSetResolver,
        private readonly RentSnapshotService $snapshotService,
        private readonly RentEffortRateService $effortRateService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function calculate(Allocation $allocation, User $actor, ?RentRuleSet $ruleSet = null, ?string $notes = null): RentCalculation
    {
        return DB::transaction(function () use ($allocation, $actor, $ruleSet, $notes): RentCalculation {
            $lockedAllocation = Allocation::query()
                ->whereKey($allocation->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedAllocation->loadMissing(['application.household.incomeRecords', 'housingUnit', 'contestHousingUnit']);
            $this->assertAllocationCanBeCalculated($lockedAllocation);
            $resolvedRuleSet = $this->ruleSetResolver->resolve($lockedAllocation, $ruleSet);
            $snapshot = $this->snapshotService->forAllocation($lockedAllocation, $resolvedRuleSet);
            $membersCount = max((int) data_get($snapshot, 'household.members_count', 0), 1);
            $monthlyIncome = DecimalMoney::normalize((string) data_get($snapshot, 'household.monthly_income', '0'));
            $annualIncome = DecimalMoney::normalize((string) data_get($snapshot, 'household.annual_income', '0'));

            [$baseRent, $status, $technicalNotes] = $this->baseRent($resolvedRuleSet, $monthlyIncome);
            $applicableRent = $baseRent === null ? null : $this->applyBounds($baseRent, $resolvedRuleSet);
            $effortRate = $applicableRent !== null ? $this->effortRateService->calculate($applicableRent, $monthlyIncome) : null;
            $depositAmount = $applicableRent !== null ? $this->depositAmount($applicableRent, $resolvedRuleSet) : null;

            if (! DecimalMoney::isPositive($monthlyIncome)) {
                $status = RentCalculationStatus::RequiresManualReview;
                $technicalNotes = trim($technicalNotes."\nRendimento mensal inexistente ou igual a zero; cálculo requer revisão manual.");
            }

            RentCalculation::query()
                ->where('allocation_id', $lockedAllocation->id)
                ->whereNotIn('status', [RentCalculationStatus::Rejected->value, RentCalculationStatus::Cancelled->value])
                ->lockForUpdate()
                ->update(['status' => RentCalculationStatus::Superseded->value]);

            $calculation = new RentCalculation([
                'rent_rule_set_id' => $resolvedRuleSet->id,
                'allocation_id' => $lockedAllocation->id,
                'application_id' => $lockedAllocation->application_id,
                'user_id' => $lockedAllocation->user_id,
                'household_id' => $lockedAllocation->application?->household_id,
                'housing_unit_id' => $lockedAllocation->housing_unit_id,
                'contest_housing_unit_id' => $lockedAllocation->contest_housing_unit_id,
                'calculation_method' => $resolvedRuleSet->calculation_method,
                'income_basis' => $resolvedRuleSet->income_basis,
                'income_period' => $resolvedRuleSet->income_period,
                'monthly_household_income' => $monthlyIncome,
                'annual_household_income' => $annualIncome,
                'monthly_income_per_capita' => DecimalMoney::divide($monthlyIncome, $membersCount),
                'annual_income_per_capita' => DecimalMoney::divide($annualIncome, $membersCount),
                'calculated_effort_rate_percentage' => $effortRate,
                'configured_effort_rate_percentage' => $resolvedRuleSet->effort_rate_percentage,
                'base_rent' => $baseRent,
                'minimum_rent' => $resolvedRuleSet->minimum_rent,
                'maximum_rent' => $resolvedRuleSet->maximum_rent,
                'applicable_rent' => $applicableRent,
                'deposit_amount' => $depositAmount,
                'calculated_at' => now(),
                'calculated_by' => $actor->id,
                'summary' => $notes,
                'technical_notes' => $technicalNotes ?: null,
                'snapshot' => $snapshot,
            ]);
            $calculation->forceFill(['status' => $status])->save();

            $this->details($calculation, $resolvedRuleSet, $monthlyIncome, $baseRent, $applicableRent, $depositAmount, $effortRate);

            $this->auditLogger->record(
                AuditEvents::CREATE,
                $calculation,
                'contracts',
                'rent_calculation_create',
                'Cálculo de renda criado.',
                metadata: ['allocation_id' => $lockedAllocation->id],
            );

            return $calculation->refresh();
        });
    }

    public function approve(RentCalculation $calculation, User $actor, ?string $notes = null): RentCalculation
    {
        return DB::transaction(function () use ($calculation, $actor, $notes): RentCalculation {
            $lockedCalculation = RentCalculation::query()
                ->whereKey($calculation->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ($this->calculationHasStatus($lockedCalculation, RentCalculationStatus::Approved)) {
                return $lockedCalculation;
            }

            if ($lockedCalculation->applicable_rent === null) {
                throw ValidationException::withMessages(['rent_calculation' => 'Não é possível aprovar cálculo sem renda aplicável.']);
            }

            $lockedCalculation->forceFill([
                'status' => RentCalculationStatus::Approved,
                'approved_at' => now(),
                'approved_by' => $actor->id,
                'summary' => $notes ?: $lockedCalculation->summary,
            ])->save();

            $this->auditLogger->record(AuditEvents::APPROVE, $lockedCalculation, 'contracts', 'rent_calculation_approve', 'Cálculo de renda aprovado.');

            return $lockedCalculation->refresh();
        });
    }

    public function reject(RentCalculation $calculation, User $actor, string $reason): RentCalculation
    {
        return DB::transaction(function () use ($calculation, $reason): RentCalculation {
            $lockedCalculation = RentCalculation::query()
                ->whereKey($calculation->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            if ($this->calculationHasStatus($lockedCalculation, RentCalculationStatus::Rejected)) {
                return $lockedCalculation;
            }

            $lockedCalculation->forceFill([
                'status' => RentCalculationStatus::Rejected,
                'technical_notes' => trim(($lockedCalculation->technical_notes ?? '')."\nRejeição: ".$reason),
            ])->save();

            $this->auditLogger->record(AuditEvents::REJECT, $lockedCalculation, 'contracts', 'rent_calculation_reject', 'Cálculo de renda rejeitado.');

            return $lockedCalculation->refresh();
        });
    }

    private function assertAllocationCanBeCalculated(Allocation $allocation): void
    {
        if (! in_array($allocation->status, [AllocationStatus::Accepted, AllocationStatus::ReadyForContract], true)) {
            throw ValidationException::withMessages(['allocation_id' => 'A atribuição deve estar aceite ou pronta para contrato.']);
        }
    }

    /**
     * @return array<string|int, mixed>
     */
    private function baseRent(RentRuleSet $ruleSet, string $monthlyIncome): array
    {
        if ($ruleSet->calculation_method === RentCalculationMethod::Manual) {
            return [null, RentCalculationStatus::RequiresManualReview, 'Método manual configurado; requer revisão manual.'];
        }

        if ($ruleSet->calculation_method === RentCalculationMethod::FixedAmount) {
            $amount = $ruleSet->minimum_rent ?? $ruleSet->maximum_rent;

            return [$amount !== null ? DecimalMoney::normalize((string) $amount) : null, $amount !== null ? RentCalculationStatus::Calculated : RentCalculationStatus::RequiresManualReview, $amount !== null ? '' : 'Valor fixo não configurado.'];
        }

        if ($ruleSet->calculation_method === RentCalculationMethod::IncomeBracket) {
            $rule = $ruleSet->rules()
                ->where('is_active', true)
                ->where('rule_type', 'income_bracket')
                ->where(fn ($query) => $query->whereNull('minimum_value')->orWhere('minimum_value', '<=', $monthlyIncome))
                ->where(fn ($query) => $query->whereNull('maximum_value')->orWhere('maximum_value', '>=', $monthlyIncome))
                ->orderBy('priority_order')
                ->first();

            if ($rule?->fixed_amount !== null) {
                return [DecimalMoney::normalize((string) $rule->fixed_amount), RentCalculationStatus::Calculated, ''];
            }

            if ($rule?->percentage !== null) {
                return [DecimalMoney::percentage($monthlyIncome, (string) $rule->percentage), RentCalculationStatus::Calculated, ''];
            }

            return [null, RentCalculationStatus::RequiresManualReview, 'Não foi encontrado escalão de renda aplicável.'];
        }

        if ($ruleSet->effort_rate_percentage === null) {
            return [null, RentCalculationStatus::RequiresManualReview, 'Taxa de esforço não configurada.'];
        }

        return [DecimalMoney::percentage($monthlyIncome, (string) $ruleSet->effort_rate_percentage), RentCalculationStatus::Calculated, ''];
    }

    private function applyBounds(string $rent, RentRuleSet $ruleSet): string
    {
        $bounded = $rent;

        if ($ruleSet->minimum_rent !== null) {
            $bounded = DecimalMoney::max($bounded, (string) $ruleSet->minimum_rent);
        }

        if ($ruleSet->maximum_rent !== null) {
            $bounded = DecimalMoney::min($bounded, (string) $ruleSet->maximum_rent);
        }

        return DecimalMoney::normalize($bounded, (int) $ruleSet->rounding_precision);
    }

    private function depositAmount(string $rent, RentRuleSet $ruleSet): string
    {
        $deposit = DecimalMoney::multiply(
            $rent,
            $ruleSet->deposit_months === null ? null : (string) $ruleSet->deposit_months,
        );

        if ($ruleSet->minimum_deposit !== null) {
            $deposit = DecimalMoney::max($deposit, (string) $ruleSet->minimum_deposit);
        }

        if ($ruleSet->maximum_deposit !== null) {
            $deposit = DecimalMoney::min($deposit, (string) $ruleSet->maximum_deposit);
        }

        return DecimalMoney::normalize($deposit);
    }

    private function details(
        RentCalculation $calculation,
        RentRuleSet $ruleSet,
        string $monthlyIncome,
        ?string $baseRent,
        ?string $applicableRent,
        ?string $depositAmount,
        ?string $effortRate,
    ): void {
        $rows = [
            ['income', 'Rendimento mensal agregado', 'income', $monthlyIncome, $monthlyIncome, 'Rendimento mensal considerado.'],
            ['base_rent', 'Renda base', 'rent', $monthlyIncome, $baseRent, 'Renda base calculada pela regra configurada.'],
            ['bounds', 'Limites de renda', 'rent', $baseRent, $applicableRent, 'Aplicação de renda mínima e máxima.'],
            ['effort_rate', 'Taxa de esforço', 'effort_rate', $applicableRent, $effortRate, 'Taxa de esforço resultante.'],
            ['deposit', 'Caução prevista', 'deposit', $applicableRent, $depositAmount, 'Caução calculada em meses de renda conforme regra.'],
        ];

        foreach ($rows as [$code, $name, $type, $input, $output, $message]) {
            $calculation->details()->create([
                'code' => $code,
                'name' => $name,
                'rule_type' => $type,
                'result' => $output === null ? RentCalculationResult::RequiresManualReview : RentCalculationResult::Applied,
                'input_value' => $input,
                'output_value' => $output,
                'message' => $message,
                'technical_message' => 'Regra: '.$ruleSet->name,
            ]);
        }
    }

    private function calculationHasStatus(RentCalculation $calculation, RentCalculationStatus $expected): bool
    {
        $status = $calculation->getAttribute('status');

        return $status === $expected || $status === $expected->value;
    }
}
