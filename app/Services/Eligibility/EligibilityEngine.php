<?php

namespace App\Services\Eligibility;

use App\Enums\EligibilityCheckStatus;
use App\Enums\EligibilityCheckType;
use App\Enums\EligibilityResult;
use App\Models\Application;
use App\Models\Contest;
use App\Models\EligibilityCheck;
use App\Models\EligibilityCriterion;
use App\Models\Program;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Support\Facades\DB;

class EligibilityEngine
{
    public function __construct(
        private readonly EligibilityRuleSetResolver $resolver,
        private readonly EligibilityDataProvider $dataProvider,
        private readonly EligibilityCriteriaEvaluator $evaluator,
        private readonly EligibilityResultAggregator $aggregator,
        private readonly EligibilitySnapshotService $snapshotService,
        private readonly EligibilityMessageService $messageService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function run(
        User $subject,
        EligibilityCheckType $type,
        ?Program $program = null,
        ?Contest $contest = null,
        ?Application $application = null,
        ?User $actor = null,
    ): EligibilityCheck {
        return DB::transaction(function () use ($subject, $type, $program, $contest, $application, $actor) {
            $contest?->loadMissing('program');

            if ($program === null && $application instanceof Application) {
                $program = $application->program;
            }

            if ($program === null && $contest instanceof Contest) {
                $program = $contest->program;
            }

            if ($contest === null && $application instanceof Application) {
                $contest = $application->contest;
            }

            $registration = $application instanceof Application
                ? $application->adhesionRegistration
                : $subject->adhesionRegistration()->first();
            $ruleSet = $this->resolver->resolve($program, $contest);
            $context = $this->dataProvider->forCandidate($subject, $program, $contest, $application);
            $executedBy = $actor instanceof User ? $actor->id : $subject->id;

            $check = new EligibilityCheck;
            $check->forceFill([
                'eligibility_rule_set_id' => $ruleSet?->id,
                'program_id' => $program?->id,
                'contest_id' => $contest?->id,
                'application_id' => $application?->id,
                'adhesion_registration_id' => $registration?->id,
                'user_id' => $subject->id,
                'check_type' => $type,
                'status' => EligibilityCheckStatus::Draft,
                'executed_by' => $executedBy,
            ])->save();

            if (! $ruleSet) {
                $result = EligibilityResult::NotApplicable;
                $check->forceFill([
                    'status' => EligibilityCheckStatus::Completed,
                    'result' => $result,
                    'summary' => $this->messageService->candidateSummary($result),
                    'missing_data' => [],
                    'warnings' => ['Não existe um conjunto de regras ativo para o contexto selecionado.'],
                    'executed_at' => now(),
                ])->save();
                $this->snapshotService->store($check, $context['snapshots']);

                return $this->audit($check, $type, $actor);
            }

            $criteria = $ruleSet->criteria()
                ->where('is_active', true)
                ->get();
            $evaluations = $criteria->map(function (EligibilityCriterion $criterion) use ($context, $check) {
                $evaluation = $this->evaluator->evaluate($criterion, $context);

                $check->results()->create([
                    'eligibility_criterion_id' => $criterion->id,
                    'code' => $criterion->code,
                    'name' => $criterion->name,
                    'category' => $criterion->category,
                    'result' => $evaluation['result'],
                    'actual_value' => $evaluation['actual_value'],
                    'expected_value' => $evaluation['expected_value'],
                    'operator' => $criterion->operator,
                    'message' => $evaluation['message'],
                    'technical_message' => $evaluation['technical_message'],
                    'requires_manual_review' => $evaluation['requires_manual_review'],
                ]);

                return compact('criterion', 'evaluation');
            });

            $result = $this->aggregator->aggregate($evaluations);
            $activeCodes = $criteria->pluck('code')->all();
            $check->forceFill([
                'status' => EligibilityCheckStatus::Completed,
                'result' => $result,
                'summary' => $this->messageService->candidateSummary($result),
                'missing_data' => array_values(array_intersect($context['missing_data'], $activeCodes)),
                'warnings' => $context['warnings'],
                'executed_at' => now(),
            ])->save();
            $this->snapshotService->store($check, $context['snapshots']);

            return $this->audit($check, $type, $actor);
        });
    }

    private function audit(EligibilityCheck $check, EligibilityCheckType $type, ?User $actor): EligibilityCheck
    {
        $this->auditLogger->record(
            event: AuditEvents::DECISION,
            auditable: $check,
            module: 'eligibility',
            action: match ($type) {
                EligibilityCheckType::CandidatePreCheck => 'run_pre_check',
                EligibilityCheckType::ApplicationFormalCheck => 'run_formal_check',
                EligibilityCheckType::BackofficeManualCheck => 'run_manual_check',
                EligibilityCheckType::SystemRecheck => 'rerun_check',
            },
            description: 'Verificação de elegibilidade executada.',
            metadata: [
                'actor_id' => $actor?->id,
                'result' => $check->result?->value,
                'check_type' => $type->value,
            ],
        );

        return $check->load(['ruleSet', 'results', 'snapshots']);
    }
}
