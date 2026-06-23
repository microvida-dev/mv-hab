<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateManualAllocationRequest;
use App\Models\Allocation;
use App\Models\AllocationRuleSet;
use App\Models\AllocationRun;
use App\Models\ContestHousingUnit;
use App\Models\DefinitiveListEntry;
use App\Services\Allocation\AllocationOfferService;
use App\Services\Allocation\RankingAllocationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AllocationController extends Controller
{
    public function __construct(
        private readonly RankingAllocationService $rankingAllocationService,
        private readonly AllocationOfferService $offerService,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Allocation::class);

        return view('backoffice.allocation.allocations.index', [
            'allocations' => Allocation::query()
                ->with(['candidate', 'contest', 'housingUnit', 'allocationRun'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function show(Allocation $allocation): View
    {
        Gate::authorize('view', $allocation);
        $allocation->load([
            'candidate',
            'application',
            'contest',
            'program',
            'housingUnit',
            'contestHousingUnit',
            'allocationRun',
            'allocationRuleSet',
            'offers',
        ]);

        return view('backoffice.allocation.allocations.show', compact('allocation'));
    }

    public function createManual(): View
    {
        Gate::authorize('create', Allocation::class);

        return view('backoffice.allocation.allocations.manual-create', [
            'runs' => AllocationRun::query()->with(['contest', 'definitiveList'])->latest()->get(),
            'entries' => DefinitiveListEntry::query()->with(['candidate', 'application', 'definitiveList'])->eligibleForAllocation()->get(),
            'units' => ContestHousingUnit::query()->available()->with(['contest', 'housingUnit'])->get(),
        ]);
    }

    public function storeManual(CreateManualAllocationRequest $request): RedirectResponse
    {
        Gate::authorize('create', Allocation::class);
        $run = AllocationRun::query()->findOrFail($request->integer('allocation_run_id'));
        $entry = DefinitiveListEntry::query()->findOrFail($request->integer('definitive_list_entry_id'));
        $unit = ContestHousingUnit::query()->findOrFail($request->integer('contest_housing_unit_id'));

        $allocation = $this->rankingAllocationService->createAllocation($run, $entry, $unit, $this->authenticatedUser($request));
        $allocation->forceFill(['manual_justification' => $request->validated('manual_justification')])->save();

        $ruleSet = $run->allocationRuleSet;

        if ($ruleSet instanceof AllocationRuleSet && $ruleSet->requires_acceptance) {
            $this->offerService->createAndIssue($allocation->refresh(), $this->authenticatedUser($request));
        }

        return to_route('backoffice.allocation.allocations.show', $allocation)->with('success', 'Atribuição manual criada.');
    }
}
