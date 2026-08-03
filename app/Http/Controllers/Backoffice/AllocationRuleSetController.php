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
use App\Models\User;
use App\Services\Municipalities\MunicipalRecordScopeService;
use App\Services\Regulatory\RegulatoryRuleSetLinkService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AllocationRuleSetController extends Controller
{
    public function __construct(
        private readonly MunicipalRecordScopeService $municipalScope,
        private readonly RegulatoryRuleSetLinkService $regulatoryLink,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAnyBackoffice', AllocationRuleSet::class);

        return view('backoffice.allocation.rule-sets.index', [
            'ruleSets' => $this->municipalScope
                ->allocationRuleSets(
                    AllocationRuleSet::query(),
                    $this->authenticatedUser($request),
                )
                ->with(['program', 'contest'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function show(AllocationRuleSet $allocationRuleSet): View
    {
        Gate::authorize('viewBackoffice', $allocationRuleSet);

        return view('backoffice.allocation.rule-sets.show', compact('allocationRuleSet'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('createBackoffice', AllocationRuleSet::class);

        return view(
            'backoffice.allocation.rule-sets.create',
            $this->formData($this->authenticatedUser($request)),
        );
    }

    public function store(StoreAllocationRuleSetRequest $request): RedirectResponse
    {
        Gate::authorize('createBackoffice', AllocationRuleSet::class);
        $ruleSet = new AllocationRuleSet($this->regulatoryLink->link(
            $request->validated(),
            $this->authenticatedUser($request),
        ));
        $ruleSet->forceFill(['created_by' => $this->authenticatedUser($request)->id, 'updated_by' => $this->authenticatedUser($request)->id])->save();

        return to_route('backoffice.allocation.rule-sets.show', $ruleSet)->with('success', 'Regra de atribuição criada.');
    }

    public function edit(
        Request $request,
        AllocationRuleSet $allocationRuleSet,
    ): View {
        Gate::authorize('updateBackoffice', $allocationRuleSet);

        return view(
            'backoffice.allocation.rule-sets.edit',
            $this->formData($this->authenticatedUser($request))
                + compact('allocationRuleSet'),
        );
    }

    public function update(UpdateAllocationRuleSetRequest $request, AllocationRuleSet $allocationRuleSet): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $allocationRuleSet);
        $allocationRuleSet->fill($this->regulatoryLink->link(
            $request->validated(),
            $this->authenticatedUser($request),
        ));
        $allocationRuleSet->forceFill(['updated_by' => $this->authenticatedUser($request)->id])->save();

        return to_route('backoffice.allocation.rule-sets.show', $allocationRuleSet)->with('success', 'Regra de atribuição atualizada.');
    }

    public function activate(Request $request, AllocationRuleSet $allocationRuleSet): RedirectResponse
    {
        Gate::authorize('approveBackoffice', $allocationRuleSet);
        $allocationRuleSet->forceFill(['status' => AllocationRuleSetStatus::Active, 'updated_by' => $this->authenticatedUser($request)->id])->save();

        return back()->with('success', 'Regra ativada.');
    }

    public function archive(Request $request, AllocationRuleSet $allocationRuleSet): RedirectResponse
    {
        Gate::authorize('updateBackoffice', $allocationRuleSet);
        $allocationRuleSet->forceFill(['status' => AllocationRuleSetStatus::Archived, 'updated_by' => $this->authenticatedUser($request)->id])->save();

        return back()->with('success', 'Regra arquivada.');
    }

    public function duplicate(Request $request, AllocationRuleSet $allocationRuleSet): RedirectResponse
    {
        Gate::authorize('duplicateBackoffice', $allocationRuleSet);
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
    private function formData(User $actor): array
    {
        return [
            'programs' => $this->municipalScope
                ->programs(Program::query(), $actor)
                ->orderBy('name')
                ->get(),
            'contests' => $this->municipalScope
                ->contests(Contest::query(), $actor)
                ->orderByDesc('created_at')
                ->get(),
            'methods' => AllocationMethod::options(),
            'statuses' => AllocationRuleSetStatus::options(),
        ];
    }
}
