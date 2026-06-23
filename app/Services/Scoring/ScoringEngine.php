<?php

namespace App\Services\Scoring;

use App\Enums\ApplicationStatus;
use App\Enums\EligibilityResult;
use App\Enums\ScoringRuleSetStatus;
use App\Enums\ScoringRunStatus;
use App\Models\Application;
use App\Models\Contest;
use App\Models\Program;
use App\Models\ScoringRuleSet;
use App\Models\ScoringRun;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ScoringEngine
{
    public function __construct(
        private readonly ScoringRuleSetResolver $resolver,
        private readonly ApplicationScoreService $scoreService,
        private readonly RankingService $rankingService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function run(
        User $actor,
        ?Program $program = null,
        ?Contest $contest = null,
        ?ScoringRuleSet $ruleSet = null,
        ?string $notes = null,
    ): ScoringRun {
        $contest?->loadMissing('program');
        $program ??= $contest?->program;
        $ruleSet ??= $this->resolver->resolveOrFail($program, $contest);

        if ($ruleSet->status !== ScoringRuleSetStatus::Active) {
            throw ValidationException::withMessages([
                'scoring_rule_set_id' => 'A matriz selecionada não está ativa.',
            ]);
        }

        $programId = $contest instanceof Contest ? $contest->program_id : ($program instanceof Program ? $program->id : $ruleSet->program_id);
        $contestId = $contest instanceof Contest ? $contest->id : $ruleSet->contest_id;

        $run = ScoringRun::query()->create([
            'scoring_rule_set_id' => $ruleSet->id,
            'program_id' => $programId,
            'contest_id' => $contestId,
            'status' => ScoringRunStatus::Draft,
            'started_by' => $actor->id,
            'notes' => $notes,
        ]);

        return $this->execute($run, $actor);
    }

    public function execute(ScoringRun $run, User $actor): ScoringRun
    {
        if ($run->applicationScores()->exists()) {
            throw ValidationException::withMessages([
                'scoring_run' => 'Esta execução já contém resultados. Crie uma nova execução para preservar histórico.',
            ]);
        }

        $run->loadMissing('ruleSet', 'program', 'contest');
        $ruleSet = $run->ruleSet;

        if (! $ruleSet instanceof ScoringRuleSet) {
            throw ValidationException::withMessages([
                'scoring_rule_set' => 'Execução sem matriz de classificação associada.',
            ]);
        }

        $run->forceFill([
            'status' => ScoringRunStatus::Running,
            'started_by' => $actor->id,
            'started_at' => now(),
            'failed_at' => null,
            'failure_reason' => null,
        ])->save();

        try {
            DB::transaction(function () use ($run, $actor, $ruleSet) {
                $applications = $this->applicationsForRun($run);
                $eligibleApplications = $applications
                    ->filter(fn (Application $application) => $application->latestEligibilityCheck?->result === EligibilityResult::Eligible)
                    ->values();

                foreach ($eligibleApplications as $application) {
                    $this->scoreService->scoreApplication($run, $application, $ruleSet, $actor);
                }

                $snapshot = $this->rankingService->rankRun($run->refresh(), $actor);
                $scores = $run->applicationScores()->get();

                $run->forceFill([
                    'status' => ScoringRunStatus::Completed,
                    'completed_at' => now(),
                    'total_applications' => $applications->count(),
                    'scored_applications' => $scores->count(),
                    'manual_review_applications' => $scores->where('requires_manual_review', true)->count(),
                    'excluded_applications' => ($applications->count() - $eligibleApplications->count())
                        + $scores->where('excluded_from_ranking', true)->count(),
                ])->save();

                $this->auditLogger->record(
                    event: AuditEvents::DECISION,
                    auditable: $run,
                    module: 'scoring',
                    action: 'scoring_run_execute',
                    description: 'Execução de classificação concluída.',
                    metadata: [
                        'actor_id' => $actor->id,
                        'snapshot_id' => $snapshot->id,
                        'scored_applications' => $scores->count(),
                    ],
                );
            });
        } catch (Throwable $exception) {
            $run->forceFill([
                'status' => ScoringRunStatus::Failed,
                'failed_at' => now(),
                'failure_reason' => Str::limit($exception->getMessage(), 3000),
            ])->save();

            throw $exception;
        }

        return $run->refresh()->load(['ruleSet', 'applicationScores.details', 'rankingSnapshots.entries']);
    }

    /**
     * @return Collection<int, Application>
     */
    private function applicationsForRun(ScoringRun $run): Collection
    {
        $compatibleStatuses = [
            ApplicationStatus::Submitted->value,
            ApplicationStatus::UnderReview->value,
            ApplicationStatus::CorrectionSubmitted->value,
            ApplicationStatus::Eligible->value,
        ];

        return Application::query()
            ->admittedForScoring()
            ->whereIn('status', $compatibleStatuses)
            ->when($run->contest_id, fn ($query) => $query->where('contest_id', $run->contest_id))
            ->when(! $run->contest_id && $run->program_id, fn ($query) => $query->where('program_id', $run->program_id))
            ->with([
                'user',
                'administrativeProcess',
                'latestEligibilityCheck',
                'household.members.incomeRecords',
                'household.incomeRecords',
                'currentHousingSituation',
            ])
            ->get();
    }
}
