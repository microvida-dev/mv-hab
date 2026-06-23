<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\AllocationMethod;
use App\Enums\AllocationRuleSetStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAllocationRuleSetRequest;
use App\Http\Requests\UpdateAllocationRuleSetRequest;
use App\Models\AllocationRuleSet;
use App\Models\Contest;
use App\Models\Program;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AllocationRuleSetController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', AllocationRuleSet::class);

        return view('backoffice.allocation.rule-sets.index', [
            'ruleSets' => AllocationRuleSet::query()->with(['program', 'contest'])->latest()->paginate(15),
        ]);
    }

    public function show(AllocationRuleSet $allocationRuleSet): View
    {
        Gate::authorize('view', $allocationRuleSet);

        return view('backoffice.allocation.rule-sets.show', compact('allocationRuleSet'));
    }

    public function create(): View
    {
        Gate::authorize('create', AllocationRuleSet::class);

        return view('backoffice.allocation.rule-sets.create', $this->formData());
    }

    public function store(StoreAllocationRuleSetRequest $request): RedirectResponse
    {
        Gate::authorize('create', AllocationRuleSet::class);
        $ruleSet = new AllocationRuleSet($request->validated());
        $ruleSet->forceFill(['created_by' => $this->authenticatedUser($request)->id, 'updated_by' => $this->authenticatedUser($request)->id])->save();

        return to_route('backoffice.allocation.rule-sets.show', $ruleSet)->with('success', 'Regra de atribuição criada.');
    }

    public function edit(AllocationRuleSet $allocationRuleSet): View
    {
        Gate::authorize('update', $allocationRuleSet);

        return view('backoffice.allocation.rule-sets.edit', $this->formData() + compact('allocationRuleSet'));
    }

    public function update(UpdateAllocationRuleSetRequest $request, AllocationRuleSet $allocationRuleSet): RedirectResponse
    {
        Gate::authorize('update', $allocationRuleSet);
        $allocationRuleSet->fill($request->validated());
        $allocationRuleSet->forceFill(['updated_by' => $this->authenticatedUser($request)->id])->save();

        return to_route('backoffice.allocation.rule-sets.show', $allocationRuleSet)->with('success', 'Regra de atribuição atualizada.');
    }

    public function activate(Request $request, AllocationRuleSet $allocationRuleSet): RedirectResponse
    {
        Gate::authorize('update', $allocationRuleSet);
        $allocationRuleSet->forceFill(['status' => AllocationRuleSetStatus::Active, 'updated_by' => $this->authenticatedUser($request)->id])->save();

        return back()->with('success', 'Regra ativada.');
    }

    public function archive(Request $request, AllocationRuleSet $allocationRuleSet): RedirectResponse
    {
        Gate::authorize('update', $allocationRuleSet);
        $allocationRuleSet->forceFill(['status' => AllocationRuleSetStatus::Archived, 'updated_by' => $this->authenticatedUser($request)->id])->save();

        return back()->with('success', 'Regra arquivada.');
    }

    public function duplicate(Request $request, AllocationRuleSet $allocationRuleSet): RedirectResponse
    {
        Gate::authorize('create', AllocationRuleSet::class);
        $copy = $allocationRuleSet->replicate(['status']);
        $copy->name = $allocationRuleSet->name.' (cópia)';
        $copy->forceFill([
            'status' => AllocationRuleSetStatus::Draft->value,
            'created_by' => $this->authenticatedUser($request)->id,
            'updated_by' => $this->authenticatedUser($request)->id,
        ]);
        $copy->save();

        return to_route('backoffice.allocation.rule-sets.edit', $copy)->with('success', 'Regra duplicada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'programs' => Program::query()->orderBy('name')->get(),
            'contests' => Contest::query()->orderByDesc('created_at')->get(),
            'methods' => AllocationMethod::options(),
            'statuses' => AllocationRuleSetStatus::options(),
        ];
    }
}
