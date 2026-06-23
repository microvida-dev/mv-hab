<?php

namespace App\Services\Allocation;

use App\Enums\AllocationMethod;
use App\Enums\AllocationRunStatus;
use App\Models\AllocationRuleSet;
use App\Models\AllocationRun;
use App\Models\ContestHousingUnit;
use App\Models\DefinitiveList;
use App\Models\DefinitiveListEntry;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class AllocationEngine
{
    public function __construct(
        private readonly AllocationRuleSetResolver $ruleSetResolver,
        private readonly RankingAllocationService $rankingAllocationService,
        private readonly PreferenceAllocationService $preferenceAllocationService,
        private readonly LotteryService $lotteryService,
        private readonly AllocationOfferService $offerService,
        private readonly ReserveListService $reserveListService,
        private readonly AllocationReportService $reportService,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function run(array $data, User $actor): AllocationRun
    {
        /** @var DefinitiveList $list */
        $list = DefinitiveList::query()->published()->findOrFail($data['definitive_list_id']);
        $ruleSet = $this->ruleSetResolver->resolveFor($list, $data['allocation_rule_set_id'] ?? null);
        $method = isset($data['allocation_method']) ? AllocationMethod::from($data['allocation_method']) : $ruleSet->allocation_method;

        if (DefinitiveListEntry::query()->where('definitive_list_id', $list->id)->eligibleForAllocation()->doesntExist()) {
            throw ValidationException::withMessages(['definitive_list_id' => 'A lista definitiva não tem entradas aptas para atribuição.']);
        }

        if (ContestHousingUnit::query()->available()->where('contest_id', $list->contest_id)->doesntExist()) {
            throw ValidationException::withMessages(['contest_housing_units' => 'Não existem habitações disponíveis no concurso.']);
        }

        $run = new AllocationRun([
            'allocation_rule_set_id' => $ruleSet->id,
            'program_id' => $list->program_id,
            'contest_id' => $list->contest_id,
            'definitive_list_id' => $list->id,
            'allocation_method' => $method,
            'notes' => $data['notes'] ?? null,
        ]);
        $run->forceFill([
            'run_number' => $this->generateRunNumber(),
            'status' => AllocationRunStatus::Running,
            'started_by' => $actor->id,
            'started_at' => now(),
        ])->save();

        try {
            DB::transaction(function () use ($run, $method, $actor, $data) {
                $result = match ($method) {
                    AllocationMethod::Lottery => $this->lotteryService->run($run->refresh(), $actor, $data),
                    AllocationMethod::PreferenceBased => $this->preferenceAllocationService->allocate($run->refresh(), $actor),
                    default => $this->rankingAllocationService->allocate($run->refresh(), $actor),
                };

                foreach ($result->allocations as $allocation) {
                    /** @var AllocationRuleSet $allocationRuleSet */
                    $allocationRuleSet = $run->getRelationValue('allocationRuleSet');

                    if ($allocationRuleSet->requires_acceptance) {
                        $this->offerService->createAndIssue($allocation, $actor);
                    } else {
                        $allocation->forceFill([
                            'status' => 'ready_for_contract',
                            'accepted_at' => now(),
                            'ready_for_contract_at' => now(),
                        ])->save();
                    }
                }

                $reserveList = $this->reserveListService->createForRun($run->refresh(), $result->reserveEntries, $actor);
                $run->forceFill([
                    'status' => AllocationRunStatus::Completed,
                    'completed_at' => now(),
                    'total_housing_units' => ContestHousingUnit::query()->where('contest_id', $run->contest_id)->count(),
                    'total_candidates' => DefinitiveListEntry::query()->where('definitive_list_id', $run->definitive_list_id)->eligibleForAllocation()->count(),
                    'total_allocations' => $run->allocations()->count(),
                    'total_reserve_entries' => $reserveList->entries()->count(),
                ])->save();

                $this->reportService->generate($run->refresh(), $actor);
                $this->auditLogger->record(AuditEvents::CREATE, $run, 'allocations', 'allocation_run_execute', 'Execução de atribuição concluída.');
            });
        } catch (Throwable $exception) {
            $run->forceFill([
                'status' => AllocationRunStatus::Failed,
                'failed_at' => now(),
                'failure_reason' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }

        $run->refresh();

        return $run->load(['allocations.offers', 'reserveList.entries', 'lotteryRun', 'reports']);
    }

    public function lock(AllocationRun $run, User $actor): AllocationRun
    {
        if (! in_array($this->allocationRunStatus($run), [AllocationRunStatus::Completed, AllocationRunStatus::Locked], true)) {
            throw ValidationException::withMessages(['allocation_run' => 'A execução deve estar concluída antes de ser bloqueada.']);
        }

        $run->forceFill(['status' => AllocationRunStatus::Locked, 'locked_at' => now(), 'locked_by' => $actor->id])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $run, 'allocations', 'allocation_run_lock', 'Execução de atribuição bloqueada.');

        return $run->refresh();
    }

    private function allocationRunStatus(AllocationRun $run): ?AllocationRunStatus
    {
        $status = $run->getAttribute('status');

        return $status instanceof AllocationRunStatus
            ? $status
            : AllocationRunStatus::tryFrom((string) $status);
    }

    public function cancel(AllocationRun $run, User $actor): AllocationRun
    {
        if ($run->allocations()->whereIn('status', ['accepted', 'ready_for_contract'])->exists()) {
            throw ValidationException::withMessages(['allocation_run' => 'Não é possível cancelar execução com atribuições aceites.']);
        }

        $run->forceFill(['status' => AllocationRunStatus::Cancelled])->save();
        $this->auditLogger->record(AuditEvents::UPDATE, $run, 'allocations', 'allocation_run_cancel', 'Execução de atribuição cancelada.');

        return $run->refresh();
    }

    private function generateRunNumber(): string
    {
        $next = AllocationRun::withTrashed()->count() + 1;

        do {
            $number = 'ATR-'.now()->format('Y').'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (AllocationRun::withTrashed()->where('run_number', $number)->exists());

        return $number;
    }
}
