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
        $allocation->loadMissing(['application.household.incomeRecords', 'housingUnit', 'contestHousingUnit']);
        $this->assertAllocationCanBeCalculated($allocation);
        $resolvedRuleSet = $this->ruleSetResolver->resolve($allocation, $ruleSet);
        $snapshot = $this->snapshotService->forAllocation($allocation, $resolvedRuleSet);
        $membersCount = max((int) data_get($snapshot, 'household.members_count', 0), 1);
        $monthlyIncome = (float) data_get($snapshot, 'household.monthly_income', 0);
        $annualIncome = (float) data_get($snapshot, 'household.annual_income', 0);

        [$baseRent, $status, $technicalNotes] = $this->baseRent($resolvedRuleSet, $monthlyIncome);
        $applicableRent = $baseRent === null ? null : $this->applyBounds($baseRent, $resolvedRuleSet);
        $effortRate = $applicableRent !== null ? $this->effortRateService->calculate($applicableRent, $monthlyIncome) : null;
        $depositAmount = $applicableRent !== null ? $this->depositAmount($applicableRent, $resolvedRuleSet) : null;

        if ($monthlyIncome <= 0) {
            $status = RentCalculationStatus::RequiresManualReview;
            $technicalNotes = trim($technicalNotes."\nRendimento mensal inexistente ou igual a zero; cálculo requer revisão manual.");
        }

        return DB::transaction(function () use ($allocation, $actor, $resolvedRuleSet, $snapshot, $membersCount, $monthlyIncome, $annualIncome, $baseRent, $status, $technicalNotes, $applicableRent, $effortRate, $depositAmount, $notes) {
            RentCalculation::query()
                ->where('allocation_id', $allocation->id)
                ->whereNotIn('status', [RentCalculationStatus::Rejected->value, RentCalculationStatus::Cancelled->value])
                ->update(['status' => RentCalculationStatus::Superseded->value]);

            $calculation = new RentCalculation([
                'rent_rule_set_id' => $resolvedRuleSet->id,
                'allocation_id' => $allocation->id,
                'application_id' => $allocation->application_id,
                'user_id' => $allocation->user_id,
                'household_id' => $allocation->application?->household_id,
                'housing_unit_id' => $allocation->housing_unit_id,
                'contest_housing_unit_id' => $allocation->contest_housing_unit_id,
                'calculation_method' => $resolvedRuleSet->calculation_method,
                'income_basis' => $resolvedRuleSet->income_basis,
                'income_period' => $resolvedRuleSet->income_period,
                'monthly_household_income' => $monthlyIncome,
                'annual_household_income' => $annualIncome,
                'monthly_income_per_capita' => round($monthlyIncome / $membersCount, 2),
                'annual_income_per_capita' => round($annualIncome / $membersCount, 2),
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
                metadata: ['allocation_id' => $allocation->id],
            );

            return $calculation->refresh();
        });
    }

    public function approve(RentCalculation $calculation, User $actor, ?string $notes = null): RentCalculation
    {
        if ($calculation->applicable_rent === null) {
            throw ValidationException::withMessages(['rent_calculation' => 'Não é possível aprovar cálculo sem renda aplicável.']);
        }

        $calculation->forceFill([
            'status' => RentCalculationStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $actor->id,
            'summary' => $notes ?: $calculation->summary,
        ])->save();

        $this->auditLogger->record(AuditEvents::APPROVE, $calculation, 'contracts', 'rent_calculation_approve', 'Cálculo de renda aprovado.');

        return $calculation->refresh();
    }

    public function reject(RentCalculation $calculation, User $actor, string $reason): RentCalculation
    {
        $calculation->forceFill([
            'status' => RentCalculationStatus::Rejected,
            'technical_notes' => trim(($calculation->technical_notes ?? '')."\nRejeição: ".$reason),
        ])->save();

        $this->auditLogger->record(AuditEvents::REJECT, $calculation, 'contracts', 'rent_calculation_reject', 'Cálculo de renda rejeitado.', metadata: ['actor_id' => $actor->id]);

        return $calculation->refresh();
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
    private function baseRent(RentRuleSet $ruleSet, float $monthlyIncome): array
    {
        if ($ruleSet->calculation_method === RentCalculationMethod::Manual) {
            return [null, RentCalculationStatus::RequiresManualReview, 'Método manual configurado; requer revisão manual.'];
        }

        if ($ruleSet->calculation_method === RentCalculationMethod::FixedAmount) {
            $amount = $ruleSet->minimum_rent ?? $ruleSet->maximum_rent;

            return [$amount !== null ? (float) $amount : null, $amount !== null ? RentCalculationStatus::Calculated : RentCalculationStatus::RequiresManualReview, $amount !== null ? '' : 'Valor fixo não configurado.'];
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
                return [(float) $rule->fixed_amount, RentCalculationStatus::Calculated, ''];
            }

            if ($rule?->percentage !== null) {
                return [($monthlyIncome * (float) $rule->percentage) / 100, RentCalculationStatus::Calculated, ''];
            }

            return [null, RentCalculationStatus::RequiresManualReview, 'Não foi encontrado escalão de renda aplicável.'];
        }

        if ($ruleSet->effort_rate_percentage === null) {
            return [null, RentCalculationStatus::RequiresManualReview, 'Taxa de esforço não configurada.'];
        }

        return [($monthlyIncome * (float) $ruleSet->effort_rate_percentage) / 100, RentCalculationStatus::Calculated, ''];
    }

    private function applyBounds(float $rent, RentRuleSet $ruleSet): float
    {
        $bounded = $rent;

        if ($ruleSet->minimum_rent !== null) {
            $bounded = max($bounded, (float) $ruleSet->minimum_rent);
        }

        if ($ruleSet->maximum_rent !== null) {
            $bounded = min($bounded, (float) $ruleSet->maximum_rent);
        }

        return round($bounded, (int) $ruleSet->rounding_precision);
    }

    private function depositAmount(float $rent, RentRuleSet $ruleSet): float
    {
        $deposit = $rent * (float) ($ruleSet->deposit_months ?? 0);

        if ($ruleSet->minimum_deposit !== null) {
            $deposit = max($deposit, (float) $ruleSet->minimum_deposit);
        }

        if ($ruleSet->maximum_deposit !== null) {
            $deposit = min($deposit, (float) $ruleSet->maximum_deposit);
        }

        return round($deposit, 2);
    }

    private function details(RentCalculation $calculation, RentRuleSet $ruleSet, float $monthlyIncome, ?float $baseRent, ?float $applicableRent, ?float $depositAmount, ?float $effortRate): void
    {
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
}
