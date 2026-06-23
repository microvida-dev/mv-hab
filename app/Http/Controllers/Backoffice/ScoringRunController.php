<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\ScoringRunStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\LockScoringRunRequest;
use App\Http\Requests\RunScoringRequest;
use App\Models\Contest;
use App\Models\Program;
use App\Models\ScoringRuleSet;
use App\Models\ScoringRun;
use App\Services\Audit\AuditLogger;
use App\Services\Scoring\ScoringEngine;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ScoringRunController extends Controller
{
    public function __construct(
        private readonly ScoringEngine $engine,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', ScoringRun::class);
        $runs = ScoringRun::query()
            ->with(['ruleSet', 'program', 'contest', 'startedBy'])
            ->withCount(['applicationScores', 'rankingSnapshots'])
            ->latest()
            ->paginate(20);

        return view('backoffice.scoring.runs.index', compact('runs'));
    }

    public function create(): View
    {
        Gate::authorize('create', ScoringRun::class);

        return view('backoffice.scoring.runs.create', $this->formData());
    }

    public function store(RunScoringRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $program = $request->filled('program_id') ? Program::query()->findOrFail($request->integer('program_id')) : null;
        $contest = $request->filled('contest_id') ? Contest::query()->findOrFail($request->integer('contest_id')) : null;
        $ruleSet = $request->filled('scoring_rule_set_id')
            ? ScoringRuleSet::query()->findOrFail($request->integer('scoring_rule_set_id'))
            : null;

        $run = $this->engine->run(
            $this->authenticatedUser($request),
            program: $program,
            contest: $contest,
            ruleSet: $ruleSet,
            notes: $data['notes'] ?? null,
        );

        return to_route('backoffice.scoring.runs.show', $run)
            ->with('success', 'Classificação executada.');
    }

    public function show(ScoringRun $scoringRun): View
    {
        Gate::authorize('view', $scoringRun);
        $scoringRun->load([
            'ruleSet',
            'program',
            'contest',
            'startedBy',
            'applicationScores.application.user',
            'rankingSnapshots.entries.application',
        ]);

        return view('backoffice.scoring.runs.show', ['run' => $scoringRun]);
    }

    public function run(Request $request, ScoringRun $scoringRun): RedirectResponse
    {
        Gate::authorize('run', $scoringRun);
        $run = $this->engine->execute($scoringRun, $this->authenticatedUser($request));

        return to_route('backoffice.scoring.runs.show', $run)
            ->with('success', 'Execução concluída.');
    }

    public function lock(LockScoringRunRequest $request, ScoringRun $scoringRun): RedirectResponse
    {
        $scoringRun->forceFill(['status' => ScoringRunStatus::Locked])->save();
        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $scoringRun,
            module: 'scoring',
            action: 'scoring_run_lock',
            description: 'Execução de classificação bloqueada.',
            metadata: ['actor_id' => $this->authenticatedUser($request)->id],
        );

        return back()->with('success', 'Execução bloqueada.');
    }

    public function cancel(Request $request, ScoringRun $scoringRun): RedirectResponse
    {
        Gate::authorize('cancel', $scoringRun);
        $scoringRun->forceFill(['status' => ScoringRunStatus::Cancelled])->save();
        $this->auditLogger->record(
            event: AuditEvents::UPDATE,
            auditable: $scoringRun,
            module: 'scoring',
            action: 'scoring_run_cancel',
            description: 'Execução de classificação cancelada.',
            metadata: ['actor_id' => $this->authenticatedUser($request)->id],
        );

        return back()->with('success', 'Execução cancelada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'contests' => Contest::query()->orderBy('title')->get(['id', 'program_id', 'title']),
            'ruleSets' => ScoringRuleSet::query()->active()->orderBy('name')->get(['id', 'program_id', 'contest_id', 'name']),
        ];
    }
}
