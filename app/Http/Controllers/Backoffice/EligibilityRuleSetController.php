<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\EligibilityRuleSetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEligibilityRuleSetRequest;
use App\Http\Requests\UpdateEligibilityRuleSetRequest;
use App\Models\Contest;
use App\Models\EligibilityRuleSet;
use App\Models\Program;
use App\Services\Audit\AuditLogger;
use App\Support\AuditEvents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EligibilityRuleSetController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        Gate::authorize('viewAny', EligibilityRuleSet::class);
        $ruleSets = EligibilityRuleSet::query()
            ->with(['program', 'contest'])
            ->withCount(['criteria', 'checks'])
            ->latest()
            ->paginate(20);

        return view('backoffice.eligibility.rule-sets.index', compact('ruleSets'));
    }

    public function create(): View
    {
        Gate::authorize('create', EligibilityRuleSet::class);

        return view('backoffice.eligibility.rule-sets.create', $this->formData());
    }

    public function store(StoreEligibilityRuleSetRequest $request): RedirectResponse
    {
        $ruleSet = DB::transaction(function () use ($request) {
            $data = $this->normalized($request->validated());
            $status = $data['status'];
            unset($data['status']);

            $ruleSet = EligibilityRuleSet::query()->create($data);
            $ruleSet->forceFill([
                'status' => $status,
                'created_by' => $this->authenticatedUser($request)->id,
                'updated_by' => $this->authenticatedUser($request)->id,
            ])->save();

            if ($status === EligibilityRuleSetStatus::Active) {
                $this->archiveConflicts($ruleSet);
            }

            return $ruleSet;
        });

        $this->audit('create', AuditEvents::CREATE, $ruleSet, $request);

        return to_route('backoffice.eligibility.rule-sets.show', $ruleSet)
            ->with('success', 'Conjunto de regras criado.');
    }

    public function show(EligibilityRuleSet $eligibilityRuleSet): View
    {
        Gate::authorize('view', $eligibilityRuleSet);
        $eligibilityRuleSet->load(['program', 'contest', 'criteria', 'createdBy', 'updatedBy'])
            ->loadCount('checks');

        return view('backoffice.eligibility.rule-sets.show', ['ruleSet' => $eligibilityRuleSet]);
    }

    public function edit(EligibilityRuleSet $eligibilityRuleSet): View
    {
        Gate::authorize('update', $eligibilityRuleSet);

        return view('backoffice.eligibility.rule-sets.edit', [
            'ruleSet' => $eligibilityRuleSet,
            ...$this->formData(),
        ]);
    }

    public function update(
        UpdateEligibilityRuleSetRequest $request,
        EligibilityRuleSet $eligibilityRuleSet,
    ): RedirectResponse {
        $data = $this->normalized($request->validated());
        $status = $data['status'];
        unset($data['status']);
        $eligibilityRuleSet->update($data);
        $eligibilityRuleSet->forceFill([
            'status' => $status,
            'updated_by' => $this->authenticatedUser($request)->id,
        ])->save();

        if ($status === EligibilityRuleSetStatus::Active) {
            $this->archiveConflicts($eligibilityRuleSet);
        }

        $this->audit('update', AuditEvents::UPDATE, $eligibilityRuleSet, $request);

        return to_route('backoffice.eligibility.rule-sets.show', $eligibilityRuleSet)
            ->with('success', 'Conjunto de regras atualizado.');
    }

    public function activate(Request $request, EligibilityRuleSet $eligibilityRuleSet): RedirectResponse
    {
        Gate::authorize('activate', $eligibilityRuleSet);
        DB::transaction(function () use ($eligibilityRuleSet, $request) {
            $this->archiveConflicts($eligibilityRuleSet);
            $eligibilityRuleSet->forceFill([
                'status' => EligibilityRuleSetStatus::Active,
                'updated_by' => $this->authenticatedUser($request)->id,
            ])->save();
        });
        $this->audit('activate', AuditEvents::APPROVE, $eligibilityRuleSet, $request);

        return back()->with('success', 'Conjunto de regras ativado.');
    }

    public function archive(Request $request, EligibilityRuleSet $eligibilityRuleSet): RedirectResponse
    {
        Gate::authorize('archive', $eligibilityRuleSet);
        $eligibilityRuleSet->forceFill([
            'status' => EligibilityRuleSetStatus::Archived,
            'updated_by' => $this->authenticatedUser($request)->id,
        ])->save();
        $this->audit('archive', AuditEvents::UPDATE, $eligibilityRuleSet, $request);

        return back()->with('success', 'Conjunto de regras arquivado.');
    }

    public function duplicate(Request $request, EligibilityRuleSet $eligibilityRuleSet): RedirectResponse
    {
        Gate::authorize('duplicate', $eligibilityRuleSet);

        $copy = DB::transaction(function () use ($request, $eligibilityRuleSet) {
            $eligibilityRuleSet->load('criteria');
            $copy = $eligibilityRuleSet->replicate();
            $copy->name = $eligibilityRuleSet->name.' — cópia';
            $copy->forceFill([
                'status' => EligibilityRuleSetStatus::Draft->value,
                'is_default' => false,
                'created_by' => $this->authenticatedUser($request)->id,
                'updated_by' => $this->authenticatedUser($request)->id,
            ]);
            $copy->save();

            foreach ($eligibilityRuleSet->criteria as $criterion) {
                $criterionCopy = $criterion->replicate();
                $criterionCopy->forceFill(['eligibility_rule_set_id' => $copy->id]);
                $criterionCopy->save();
            }

            return $copy;
        });

        $this->audit('duplicate', AuditEvents::CREATE, $copy, $request);

        return to_route('backoffice.eligibility.rule-sets.edit', $copy)
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
            'statuses' => EligibilityRuleSetStatus::options(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalized(array $data): array
    {
        $data['is_default'] = (bool) ($data['is_default'] ?? false);
        $data['status'] = EligibilityRuleSetStatus::from($data['status']);

        return $data;
    }

    private function archiveConflicts(EligibilityRuleSet $ruleSet): void
    {
        EligibilityRuleSet::query()
            ->whereKeyNot($ruleSet->id)
            ->where('status', EligibilityRuleSetStatus::Active->value)
            ->where('program_id', $ruleSet->program_id)
            ->when(
                $ruleSet->contest_id,
                fn ($query) => $query->where('contest_id', $ruleSet->contest_id),
                fn ($query) => $query->whereNull('contest_id'),
            )
            ->update(['status' => EligibilityRuleSetStatus::Archived->value]);
    }

    private function audit(string $action, string $event, EligibilityRuleSet $ruleSet, Request $request): void
    {
        $this->auditLogger->record(
            event: $event,
            auditable: $ruleSet,
            module: 'eligibility',
            action: 'rule_set_'.$action,
            description: 'Conjunto de regras de elegibilidade: '.$action.'.',
            metadata: ['actor_id' => $this->authenticatedUser($request)->id],
        );
    }
}
