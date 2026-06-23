<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\ScoringRuleSetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScoringRuleSetRequest;
use App\Http\Requests\UpdateScoringRuleSetRequest;
use App\Models\Contest;
use App\Models\Program;
use App\Models\ScoringRuleSet;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ScoringRuleSetController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        Gate::authorize('viewAny', ScoringRuleSet::class);
        $ruleSets = ScoringRuleSet::query()
            ->with(['program', 'contest'])
            ->withCount(['criteria', 'tieBreakerRules', 'runs'])
            ->latest()
            ->paginate(20);

        return view('backoffice.scoring.rule-sets.index', compact('ruleSets'));
    }

    public function create(): View
    {
        Gate::authorize('create', ScoringRuleSet::class);

        return view('backoffice.scoring.rule-sets.create', $this->formData());
    }

    public function store(StoreScoringRuleSetRequest $request): RedirectResponse
    {
        $ruleSet = DB::transaction(function () use ($request) {
            $data = $this->normalized($request->validated());
            $ruleSet = ScoringRuleSet::query()->create($data);
            $ruleSet->forceFill([
                'created_by' => $this->authenticatedUser($request)->id,
                'updated_by' => $this->authenticatedUser($request)->id,
            ])->save();

            if ($ruleSet->status === ScoringRuleSetStatus::Active) {
                $this->archiveConflicts($ruleSet);
            }

            return $ruleSet;
        });

        $this->audit('rule_set_create', AuditEvents::CREATE, $ruleSet, $request);

        return to_route('backoffice.scoring.rule-sets.show', $ruleSet)
            ->with('success', 'Matriz de classificação criada.');
    }

    public function show(ScoringRuleSet $scoringRuleSet): View
    {
        Gate::authorize('view', $scoringRuleSet);
        $scoringRuleSet->load(['program', 'contest', 'criteria', 'tieBreakerRules', 'runs'])
            ->loadCount(['criteria', 'tieBreakerRules', 'runs']);

        return view('backoffice.scoring.rule-sets.show', ['ruleSet' => $scoringRuleSet]);
    }

    public function edit(ScoringRuleSet $scoringRuleSet): View
    {
        Gate::authorize('update', $scoringRuleSet);

        return view('backoffice.scoring.rule-sets.edit', [
            'ruleSet' => $scoringRuleSet,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateScoringRuleSetRequest $request, ScoringRuleSet $scoringRuleSet): RedirectResponse
    {
        DB::transaction(function () use ($request, $scoringRuleSet) {
            $scoringRuleSet->update($this->normalized($request->validated()));
            $scoringRuleSet->forceFill(['updated_by' => $this->authenticatedUser($request)->id])->save();

            if ($scoringRuleSet->status === ScoringRuleSetStatus::Active) {
                $this->archiveConflicts($scoringRuleSet);
            }
        });

        $this->audit('rule_set_update', AuditEvents::UPDATE, $scoringRuleSet, $request);

        return to_route('backoffice.scoring.rule-sets.show', $scoringRuleSet)
            ->with('success', 'Matriz de classificação atualizada.');
    }

    public function activate(Request $request, ScoringRuleSet $scoringRuleSet): RedirectResponse
    {
        Gate::authorize('activate', $scoringRuleSet);
        DB::transaction(function () use ($request, $scoringRuleSet) {
            $this->archiveConflicts($scoringRuleSet);
            $scoringRuleSet->forceFill([
                'status' => ScoringRuleSetStatus::Active,
                'updated_by' => $this->authenticatedUser($request)->id,
            ])->save();
        });
        $this->audit('rule_set_activate', AuditEvents::APPROVE, $scoringRuleSet, $request);

        return back()->with('success', 'Matriz ativada.');
    }

    public function archive(Request $request, ScoringRuleSet $scoringRuleSet): RedirectResponse
    {
        Gate::authorize('archive', $scoringRuleSet);
        $scoringRuleSet->forceFill([
            'status' => ScoringRuleSetStatus::Archived,
            'updated_by' => $this->authenticatedUser($request)->id,
        ])->save();
        $this->audit('rule_set_archive', AuditEvents::UPDATE, $scoringRuleSet, $request);

        return back()->with('success', 'Matriz arquivada.');
    }

    public function duplicate(Request $request, ScoringRuleSet $scoringRuleSet): RedirectResponse
    {
        Gate::authorize('duplicate', $scoringRuleSet);

        $copy = DB::transaction(function () use ($request, $scoringRuleSet) {
            $scoringRuleSet->load(['criteria.rules', 'tieBreakerRules']);
            $copy = $scoringRuleSet->replicate();
            $copy->name = $scoringRuleSet->name.' - copia';
            $copy->forceFill([
                'status' => ScoringRuleSetStatus::Draft->value,
                'is_default' => false,
                'created_by' => $this->authenticatedUser($request)->id,
                'updated_by' => $this->authenticatedUser($request)->id,
            ]);
            $copy->save();

            foreach ($scoringRuleSet->criteria as $criterion) {
                $criterionCopy = $criterion->replicate();
                $criterionCopy->forceFill(['scoring_rule_set_id' => $copy->id]);
                $criterionCopy->save();

                foreach ($criterion->rules as $rule) {
                    $ruleCopy = $rule->replicate();
                    $ruleCopy->forceFill(['scoring_criterion_id' => $criterionCopy->id]);
                    $ruleCopy->save();
                }
            }

            foreach ($scoringRuleSet->tieBreakerRules as $tieBreakerRule) {
                $tieBreakerCopy = $tieBreakerRule->replicate();
                $tieBreakerCopy->forceFill(['scoring_rule_set_id' => $copy->id]);
                $tieBreakerCopy->save();
            }

            return $copy;
        });

        $this->audit('rule_set_duplicate', AuditEvents::CREATE, $copy, $request);

        return to_route('backoffice.scoring.rule-sets.edit', $copy)
            ->with('success', 'Cópia criada em rascunho.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'contests' => Contest::query()->orderBy('title')->get(['id', 'program_id', 'title']),
            'statuses' => ScoringRuleSetStatus::options(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalized(array $data): array
    {
        $data['is_default'] = (bool) ($data['is_default'] ?? false);
        $data['program_id'] = $data['program_id'] ?? null;
        $data['contest_id'] = $data['contest_id'] ?? null;

        return $data;
    }

    private function archiveConflicts(ScoringRuleSet $ruleSet): void
    {
        ScoringRuleSet::query()
            ->whereKeyNot($ruleSet->id)
            ->where('status', ScoringRuleSetStatus::Active->value)
            ->where('program_id', $ruleSet->program_id)
            ->when(
                $ruleSet->contest_id,
                fn ($query) => $query->where('contest_id', $ruleSet->contest_id),
                fn ($query) => $query->whereNull('contest_id'),
            )
            ->update(['status' => ScoringRuleSetStatus::Archived->value]);
    }

    private function audit(string $action, string $event, ScoringRuleSet $ruleSet, Request $request): void
    {
        $this->auditLogger->record(
            event: $event,
            auditable: $ruleSet,
            module: 'scoring',
            action: $action,
            description: 'Matriz de classificação: '.$action.'.',
            metadata: ['actor_id' => $this->authenticatedUser($request)->id],
        );
    }
}
